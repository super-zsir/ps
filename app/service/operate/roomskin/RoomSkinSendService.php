<?php

namespace Imee\Service\Operate\Roomskin;

use Imee\Exception\ApiException;
use Imee\Models\Xs\XsPopupsConfig;
use Imee\Models\Xs\XsRoomSkin;
use Imee\Models\Xs\XsUserProfile;
use Imee\Models\Xs\XsUserRoomSkin;
use Imee\Models\Xsst\BmsOperateLog;
use Imee\Models\Xsst\XsstUserRoomSkinLog;
use Imee\Service\Domain\Service\Traits\UserInfoTrait;
use Imee\Service\Helper;
use Imee\Service\Lesscode\Traits\Curd\ListTrait;
use Imee\Service\Rpc\PsService;
use OSS\OssUpload;
use phpDocumentor\Reflection\DocBlock\Description;

class RoomSkinSendService
{
    use UserInfoTrait;

    /**
     * @var PsService $rpcService
     */
    private $rpcService;

    public function __construct()
    {
        $this->rpcService = new PsService();
    }

    public function getList(array $params)
    {
        $conditions = $this->getConditions($params);
        $list = XsstUserRoomSkinLog::getListAndTotal($conditions, '*', 'id desc', $params['page'] ?? 1, $params['limit'] ?? 15);
        if (empty($list['data'])) {
            return [];
        }
        $time = time();
        foreach ($list['data'] as &$item) {
            if ($item['batch_operate_file']) {
                $item['batch_operate_file'] = [
                    'title'        => 'DownLoad',
                    'value'        => '下载',
                    'type'         => 'url',
                    'url'          => Helper::getHeadUrl($item['batch_operate_file']),
                    'resourceType' => 'outside'
                ];
            } else {
                $effectiveTime = $item['effective_time'] - $time;
                $item['effective_time'] = $effectiveTime < 0 ? '已失效' : ceil($effectiveTime / 86400);
            }
            $item['dateline'] = Helper::now($item['dateline']);
        }

        return $list;
    }

    public function send(array $params)
    {
        [$uids, $commoditys] = $this->validation($params['uid'], $params['commodity']);

        $data = $this->formatSendData($uids, $commoditys, $params['remarks'] ?? '', $params['admin_id']);
        $this->checkSendNum($data);
        $this->callRpc($data);

        $insertData = array_map(function($v) {
            unset($v['use_term']);
            return $v;
        }, $data);
        // 添加下发记录
        XsstUserRoomSkinLog::addBatch($insertData);
    }

    public function sendBatch(array $params, string $url)
    {
        $this->checkSendNum($params);
        $this->callRpc($params);
        // 添加下发记录（批量上传只保留一条数据包含上传文件）
        XsstUserRoomSkinLog::add([
            'batch_operate_file' => $url,
            'dateline' => time(),
        ]);
    }

    public function callRpc(array $params)
    {
        $data = [];

        foreach($params as $item) {
            $data[] = [
                'uid'       => (int)$item['uid'],
                'skin_id'   => (int)$item['skin_id'],
                'use_term'  => (int)$item['use_term'],
                'remark'    => $item['remarks'] ?? ''
            ];
        }
        [$res, $msg] = $this->rpcService->sendUserRoomSkin($data);

        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
    }

    public function checkSendNum($data)
    {
        // 限制每次只能下发500
        if (count($data) > 500) {
            throw new ApiException(ApiException::MSG_ERROR, '单次最多下发500条');
        }
    }

    public function formatSendData($uids, $commoditys, $remarks, $adminId)
    {
        $data = [];
        $time = time();
        $admin = Helper::getAdminName($adminId);
        foreach ($uids as $uid) {
            foreach ($commoditys as $commodity) {
                $data[] = [
                    'type' => $commodity['type'],
                    'uid'  => $uid,
                    'skin_id' => $commodity['skin_id'],
                    'effective_time' => $time + 86400 * $commodity['effective_time'],
                    'remarks' => $remarks,
                    'operator' => $admin,
                    'dateline' => $time,
                    'use_term' => $commodity['use_term'],
                ];
            }
        }

        return $data;
    }

    public function validation($uids, $commoditys)
    {
        if (!is_array($uids)) {
            $uids = Helper::formatIdString($uids);
        }

        $uids = $this->checkUid($uids);
        $commoditys = $this->checkCommodity($commoditys);

        return [$uids, $commoditys];
    }

    private function checkCommodity($commodity)
    {
        if (empty($commodity)) {
            throw new ApiException(ApiException::MSG_ERROR, '物品必须配置');
        }

        foreach ($commodity as &$item) {
            if (!preg_match('/^[1-9][0-9]*$/', $item['effective_time'])) {
                throw new ApiException(ApiException::MSG_ERROR, '物品参数配置错误，请检查皮肤是否存在及有效时间是否为正整数');
            }

            $info = XsRoomSkin::getInfo($item['skin_id']);

            if (!$info) {
                throw new ApiException(ApiException::MSG_ERROR, '皮肤id有误，请重新进行选择');
            }
            $item['type'] = $info['type'];
            $item['use_term'] = $item['effective_time'] * 86400;
        }

        return $commodity;
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

    private function checkUid($uids)
    {
        if (!is_array($uids)) {
            $uids = Helper::formatIdString($uids);
        }

        $errorUid = XsUserProfile::checkUid($uids);

        if ($errorUid && is_array($errorUid)) {
            throw new ApiException(ApiException::MSG_ERROR, implode(',', $errorUid) . '以上UID错误');
        }

        return $uids;
    }

    private function getConditions(array $params)
    {
        $conditions = [];

        if (isset($params['dateline_sdate']) && !empty($params['dateline_sdate'])
            && isset($params['dateline_edate']) && !empty($params['dateline_edate'])) {
            $conditions[] = ['dateline', '>=', strtotime($params['dateline_sdate'])];
            $conditions[] = ['dateline', '<', strtotime($params['dateline_edate'])  + 86400];
        }

        return $conditions;
    }
}