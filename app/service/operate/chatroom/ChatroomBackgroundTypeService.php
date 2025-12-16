<?php

namespace Imee\Service\Operate\Chatroom;

use Imee\Exception\ApiException;
use Imee\Models\Xsst\XsstChatroomBackground;
use Imee\Models\Xsst\XsstChatroomBackgroundHistory;
use Imee\Service\Helper;
use OSS\OssUpload;

class ChatroomBackgroundTypeService
{
    /**
     * @var XsstChatroomBackground $model
     */
    private $model = XsstChatroomBackground::class;

    /**
     * @var XsstChatroomBackgroundHistory $logModel
     */
    private $logModel = XsstChatroomBackgroundHistory::class;

    public function getListAndTotal(array $params): array
    {
        $conditions = $this->getConditions($params);
        $list = $this->model::getListAndTotal($conditions, '*', 'id desc', $params['page'] ?? 1, $params['limit'] ?? 15);
        if (empty($list)) {
            return $list;
        }

        $logs = $this->logModel::getFirstLogList(Helper::arrayFilter($list['data'], 'id'));
        foreach ($list['data'] as &$item) {
            $item['dateline'] = Helper::now($item['dateline']);
            $item['icon'] = sprintf($this->model::ICON_PATH, $item['type']);
            $item['icon2'] = sprintf($this->model::ICON2_PATH, $item['type']);
            $item['icon_all'] = Helper::getHeadUrl($item['icon']);
            $item['icon2_all'] = Helper::getHeadUrl($item['icon2']);
            $item['update_time'] = isset($logs[$item['id']]) ? Helper::now($logs[$item['id']]['dateline']) : '';
            $item['update_name'] = isset($logs[$item['id']]) ? $logs[$item['id']]['update_uname'] : '';

        }

        return $list;
    }

    public function create(array $params): array
    {
        $type = $params['type'];
        $icon = $params['icon'];
        $icon2 = $params['icon2'];

        if ($this->model::getInfoByType($type)) {
            throw new ApiException(ApiException::MSG_ERROR, '该类型已存在');
        }

        // 移动资源到指定目录
        if (!$this->copyBackgroundIcon($type, $icon, $icon2)) {
            throw new ApiException(ApiException::MSG_ERROR, '文件系统故障');
        };

        $data = [
            'type'     => $type,
            'app_id'   => APP_ID,
            'dateline' => time()
        ];

        list($res, $id) = $this->model::add($data);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, 'add fail, reason：' . $id);
        }

        $logData = [
            'type'  => $type,
            'icon'  => $icon,
            'icon2' => $icon2
        ];
        // 添加日志
        $this->addLog($id, $logData, $params['admin_uid']);
        return ['id' => $id, 'after_json' => $data];
    }

    public function modify(array $params): array
    {
        $id = $params['id'];
        $icon = $params['icon'];
        $icon2 = $params['icon2'];

        $info = $this->model::findOne($id);
        if (!$info) {
            throw new ApiException(ApiException::MSG_ERROR, '数据错误');
        }

        // 判断是否修改了资源
        $update = [];
        if (!str_contains($icon, $this->model::ICON_PATH_PREFIX)) {
            $update['icon'] = $icon;
        }
        if (!str_contains($icon2, $this->model::ICON_PATH_PREFIX)) {
            $update['icon2'] = $icon;
        }

        if ($update) {
            // 移动资源到指定目录
            if (!$this->copyBackgroundIcon($info['type'], $icon, $icon2)) {
                throw new ApiException(ApiException::MSG_ERROR, '文件系统故障');
            }
            $this->addLog($id, $update, $params['admin_uid']);
        }

        return ['id' => $id, 'after_json' => $update];
    }

    /**
     * 获取修改日志
     * @param array $params
     * @return array
     * @throws ApiException
     */
    public function getHistoryListAndTotal(array $params): array
    {
        $id = intval($params['id'] ?? 0);
        if (empty($id)) {
            throw new ApiException(ApiException::MSG_ERROR, 'id参数错误');
        }
        $conditions = [['sid', '=', $id]];
        $list = $this->logModel::getListAndTotal($conditions, '*', 'id desc', $params['page'] ?? 1, $params['limit'] ?? 15);
        if (empty($list['data'])) {
            return $list;
        }

        foreach ($list['data'] as &$item) {
            $content = json_decode($item['content'], true);
            $item = $item + $content;
            $item['icon'] = isset($item['icon']) ? Helper::getHeadUrl($item['icon']) : '';
            $item['icon2'] = isset($item['icon2']) ? Helper::getHeadUrl($item['icon2']) : '';
            $item['dateline'] = Helper::now($item['dateline']);
        }

        return $list;
    }

    private function addLog($id, $data, $adminUid): void
    {
        $logData = [
            'sid'          => $id,
            'content'      => json_encode($data),
            'update_uid'   => $adminUid,
            'update_uname' => Helper::getAdminName($adminUid),
            'dateline'     => time()
        ];

        list($res, $msg) = $this->logModel::add($logData);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, 'log add fail, reason:' . $msg);
        }
    }

    private function copyBackgroundIcon(string $type, string $icon, string $icon2): bool
    {
        if (empty($icon) && empty($icon2)) {
            return false;
        }

        $bucket = ENV == 'dev' ? OssUpload::PS_DEV_IMAGE : OssUpload::PS_XS_IMAGE;
        $ossUpload = new OssUpload($bucket);
        $client = $ossUpload->client();
        if (!$client) {
            return false;
        }
        if ($icon) {
            $client->copyObject($bucket, $icon, $bucket, sprintf($this->model::ICON_PATH, $type));
        }
        if ($icon2) {
            $client->copyObject($bucket, $icon2, $bucket, sprintf($this->model::ICON2_PATH, $type));
        }

        return true;
    }

    private function getConditions(array $params): array
    {
        $conditions = [['app_id', '=', APP_ID]];
        $type = trim($params['type'] ?? '');
        if ($type) {
            $type = str_replace('，', ',', $type);
            $type = explode(',', trim($type, ','));
            $conditions[] = ['type', 'IN', $type];
        }

        return $conditions;
    }
}