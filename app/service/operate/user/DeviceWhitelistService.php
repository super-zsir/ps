<?php

namespace Imee\Service\Operate\User;

use Imee\Exception\ApiException;
use Imee\Models\Config\XsstDeviceWhitelist;
use Imee\Models\Xsst\BmsWhitelistSetting;
use Imee\Service\Helper;

class DeviceWhitelistService
{
    public function getList(array $params): array
    {
        $conditions = $this->getConditions($params);
        $list = XsstDeviceWhitelist::getListAndTotal($conditions, '*', 'id desc', $params['page'] ?? 1, $params['limit'] ?? 15);
        if (empty($list['data'])) {
            return $list;
        }
        // 获取设备白名单
        $whitelists = BmsWhitelistSetting::getWhiteListValueByType('device');
        foreach ($list['data'] as &$v) {
            $v['type']        = $v['whitelist_type'] . '-' . ($whitelists[$v['whitelist_type']] ?? '');
            $v['dateline']    = Helper::now($v['created_time']);
            $v['create_name'] = Helper::getAdminName($v['admin_id']);
        }
        return $list;
    }

    public function add(array $params)
    {
        $data = [
            'device_type' => $params['device_type'],
            'mac' => trim($params['mac']),
            'whitelist_type' => $params['whitelist_type'],
            'created_time' => time(),
            'admin_id' => Helper::getSystemUid(),
            'remark' => $params['remark'] ?? '',
        ];

        $exists = XsstDeviceWhitelist::findOneByWhere([
            ['mac', '=', $data['mac']],
            ['whitelist_type', '=', $data['whitelist_type']],
        ]);

        if ($exists) {
            throw new ApiException(ApiException::MSG_ERROR, '该设备在此类型下已存在');
        }

        list($res, $id) = XsstDeviceWhitelist::add($data);

        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $id);
        }

        return $id;
    }

    public function addBatch(array $params)
    {
        if (count($params) > 500) {
            throw new ApiException(ApiException::MSG_ERROR, '批量创建最大支持500条数据，超过请分批上传');
        }
        $baseData = [
            'created_time' => time(),
            'admin_id' => Helper::getSystemUid(),
        ];
        $data = [];
        foreach ($params as $item) {
            $data[] = array_merge($item, $baseData);
        }
        list($res, $msg, $rows) = XsstDeviceWhitelist::addBatch($data, 'INSERT IGNORE');

        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg || '批量添加失败');
        }
    }

    public function deleteBatch(array $ids): void
    {
        list($res, $msg, $rows) = XsstDeviceWhitelist::deleteByWhere([
            ['id', 'IN', $ids]
        ]);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg || '删除失败');
        }
    }
    
    public static function getWhiteListValue()
    {
        $types = BmsWhitelistSetting::getWhitelistByType('device', Helper::getSystemUid());

        return array_keys($types);
    }

    public function getConditions(array $params): array
    {
        $conditions = [
            ['deleted', '=', XsstDeviceWhitelist::DELETED_NO]
        ];

        if (isset($params['mac']) && !empty($params['mac'])) {
            $conditions[] = ['mac', 'like', "%{$params['mac']}%"];
        }

        if (isset($params['whitelist_type']) && !empty($params['whitelist_type'])) {
            $conditions[] = ['whitelist_type', '=', $params['whitelist_type']];
        }

        return $conditions;
    }
}