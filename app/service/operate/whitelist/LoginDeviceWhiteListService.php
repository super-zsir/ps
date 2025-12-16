<?php

namespace Imee\Service\Operate\Whitelist;

use Imee\Exception\ApiException;
use Imee\Models\Xs\XsLoginRegisterWhitelist;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;

class LoginDeviceWhiteListService
{
    public function getList(array $params): array
    {
        $conditions = $this->getConditions($params);
        $list = XsLoginRegisterWhitelist::getListAndTotal($conditions, '*', 'id desc', $params['page'] ?? 1, $params['limit'] ?? 15);

        foreach ($list['data'] as &$item) {
            $item['dateline'] = Helper::now($item['dateline']);
        }

        return $list;
    }

    public function add(array $params)
    {
        return $this->addBatch([
            'data' => [
                [
                    'object_id' => $params['object_id'],
                    'comments' => $params['comments'] ?? '',
                ]
            ],
        ]);
    }

    public function addBatch(array $params)
    {
        $count = XsLoginRegisterWhitelist::getCount([]);
        if ($count >= 5000) {
            throw new ApiException(ApiException::MSG_ERROR, '名单总行数已超过5000，不可添加');
        }
        $objectIds = array_column($params['data'], 'object_id');
        $objectIds = array_filter($objectIds);
        $existsObjectIds = XsLoginRegisterWhitelist::getListByObjectIdChunk($objectIds);
        $existsObjectIds = array_column($existsObjectIds, 'object_id');
        $data = [];
        $adminId = Helper::getSystemUid();
        $admin = Helper::getAdminName($adminId);
        foreach ($params['data'] as $item) {
            if (!in_array($item['object_id'], $existsObjectIds) && !empty($item['object_id'])) {
                $data[] = [
                    'object_id' => trim($item['object_id']),
                    'operator' => $admin,
                    'comments' => $item['comments'] ?? '',
                    'list_type' => XsLoginRegisterWhitelist::WHITE_TYPE,
                ];
            }
        }

        if (empty($data)) {
            throw new ApiException(ApiException::MSG_ERROR, '当前添加的object_id 地址已存在，无需重复添加');
        }
        [$res, $msg, $id] = (new PsService())->addLoginRegisterWhiteList($data);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        return $id;
    }

    public function delete(int $id): bool
    {
        return XsLoginRegisterWhitelist::deleteById($id);
    }

    public function delBatch(array $ids): bool
    {
        [$res, $msg, $rows] = XsLoginRegisterWhitelist::deleteByWhere([
            ['id', 'in', $ids]
        ]);

        if (!$res || $rows != count($ids)) {
            throw new ApiException(ApiException::MSG_ERROR, $msg ?? '未全部删除成功');
        }

        return true;
    }

    public function getConditions(array $params)
    {
        $conditions = [
            ['type', '=', XsLoginRegisterWhitelist::WHITE_TYPE]
        ];
        if (!empty($params['object_id'])) {
            $conditions[] = ['object_id', 'like', "%{$params['object_id']}%"];
        }
        return $conditions;
    }
}