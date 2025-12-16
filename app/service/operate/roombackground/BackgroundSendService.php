<?php

namespace Imee\Service\Operate\Roombackground;

use Imee\Comp\Common\Log\Service\OperateLog;
use Imee\Models\Xs\XsDropChatroomBackgroundLog;
use Imee\Models\Xs\XsUserProfile;
use Imee\Models\Xsst\BmsOperateLog;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;
use OSS\OssUpload;

class BackgroundSendService
{
    /**
     * @var PsService
     */
    private $rpcService;

    public function __construct()
    {
        $this->rpcService = new PsService();
    }

    public function getList(array $params): array
    {
        $conditions = [];
        if (isset($params['time_sdate']) && !empty($params['time_sdate']) &&
            isset($params['time_edate']) && !empty($params['time_edate'])) {
            $conditions = [
                ['time', '>=', strtotime($params['time_sdate'])],
                ['time', '<=', strtotime($params['time_edate']) + 86400]
            ];
        }
        $list = XsDropChatroomBackgroundLog::getListAndTotal($conditions, '*', 'id desc', $params['page'], $params['limit']);

        if (empty($list)) {
            return ['data' => [], 'total' => 0];
        }

        foreach ($list['data'] as &$item) {
            $item['duration'] = $this->formatDuration($item['duration']);
            $item['source'] = $item['source_desc'];
            $item['time'] = !empty($item['time']) ? Helper::now($item['time']) : '';
            $item['batch_operate_file'] = $this->parseModal($item['batch_operate_file']);
        }
        return $list;
    }

    private function formatDuration($duration)
    {
        if (empty($duration)) {
            return 0;
        }

        return ceil(((int) $duration) / 86400);
    }

    public function parseModal($file)
    {
        if (empty($file)) {
            return '';
        }

        return [
            'title' => 'DownLoad',
            'value' => '下载',
            'type' => 'url',
            'url' => $file,
            'resourceType' => 'outside'
        ];
    }

    public function send(array $params): array
    {
        $uidStr = str_replace('，', ',', $params['uid']);
        $uidArr = explode(',', $uidStr);

        list($vRes, $ids) = $this->validationUid($uidArr);
        if (!$vRes) {
            return [false, $ids];
        }

        $data = [
            'bg_id'       => (int)$params['bg_id'],
            'duration'    => ((int)$params['duration']) * 86400,
            'uid_list'    => $ids,
            'operator'    => Helper::getAdminName($params['admin_id']),
            'source_desc' => $params['source'] ?? '官方发放'
        ];
        list($res, $msg) = $this->rpcService->dropRoomBackground($data);
        if (!$res) {
            return [false, $msg];
        }
        $this->addLog($msg, $data, BmsOperateLog::ACTION_ADD, $params['admin_id']);

        return [true, ''];
    }

    public function sendBatch(array $params)
    {
        $sendList = [];
        foreach ($params['items'] as $item) {
            $source = trim($item['source'] ?? '');
            $sendList[] = [
                'uid'         => (int)$item['uid'],
                'bg_id'       => (int)$item['bg_id'],
                'duration'    => ((int)$item['duration']) * 86400,
                'source_desc' => $source ?: '官方发放',
            ];
        }

        $data = [
            'items' => $sendList,
            'operator' => Helper::getAdminName($params['admin_id']),
            'file' => $params['file']
        ];

        list($res, $msg) = $this->rpcService->mDropRoomBackground($data);

        if (!$res) {
            return [false, $msg];
        }

        $this->addLog($msg, $data, BmsOperateLog::ACTION_ADD, $params['admin_id']);

        return [true, ''];
    }

    public function uploadOss($file)
    {
        $bucket = ENV == 'dev' ? BUCKET_DEV : BUCKET_ONLINE;
        $client = new OssUpload($bucket);
        $fileName = $client->moveFile($file->getTempName(), 'csv');
        if ($fileName !== false) {
            return Helper::getHeadUrl($fileName);
        }
        return false;
    }

    public function validationUid($uids)
    {
        $uids = array_map('intval', $uids);
        $uids = array_filter($uids);
        $uids = array_unique($uids);
        $uids = array_values($uids);

        if (count($uids) > 500) {
            return [false, 'UID最多输入500个'];
        }

        $uidList = XsUserProfile::getUserProfileBatch($uids);

        if (empty($uidList)) {
            $uidStr = implode(',', $uids);
            return [false, 'UID：' . $uidStr . '不存在'];
        }

        $existsUid = array_keys($uidList);

        $diffArr = array_diff($uids, $existsUid);

        if (!empty($diffArr)) {
            $diffStr = implode(',', $diffArr);
            return [false, 'UID：' . $diffStr . '不存在'];
        }

        return [true, $uids];

    }

    public function addLog($id,  $afterJson, $action, $adminId, $beforeJson = '', $content = '')
    {
        $data = [
            'model_id'     => $id,
            'model'        => 'background_send',
            'action'       => $action,
            'content'      => $content,
            'before_json'  => $beforeJson,
            'after_json'   => $afterJson,
            'operate_id'   => $adminId,
        ];

        OperateLog::addOperateLog($data);
    }
}