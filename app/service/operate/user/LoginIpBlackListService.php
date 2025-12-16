<?php

namespace Imee\Service\Operate\User;

use Imee\Exception\ApiException;
use Imee\Models\Xs\XsLoginRegisterWhitelist;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;

class LoginIpBlackListService
{
    public function getList(array $params): array
    {
        $conditions = $this->getConditions($params);
        $list = XsLoginRegisterWhitelist::getListAndTotal($conditions, '*', 'id desc', $params['page'] ?? 1, $params['limit'] ?? 15);
        foreach ($list['data'] as &$item) {
            $item['dateline'] && $item['dateline'] = Helper::now($item['dateline']);
        }

        return $list;
    }

    public function create(array $params)
    {
        $count = XsLoginRegisterWhitelist::getCount([]);
        if ($count >= 5000) {
            throw new ApiException(ApiException::MSG_ERROR, '名单总行数已超过5000，不可添加');
        }
        $admin = Helper::getSystemUserInfo();

        $objectId = trim($params['object_id']);
        $exists = XsLoginRegisterWhitelist::findOneByWhere([['object_id', '=', $objectId]]);
        if ($exists) {
            throw new ApiException(ApiException::MSG_ERROR, '设备/IP 已存在');
        }

        $data = [
            [
                'object_id' => trim($params['object_id']),
                'operator' => $admin['user_name'],
                'comments' => trim($params['comments'] ?? ''),
                'list_type' => (int)$params['type'],
            ]
        ];
        // 此接口为批量接口
        list($res, $msg, $id) = (new PsService())->addLoginRegisterWhiteList($data);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        return $id;
    }

    public function delete(int $id)
    {
        $res = XsLoginRegisterWhitelist::deleteById($id);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, '删除失败');
        }
    }

    public function getConditions(array $params): array
    {
        if (isset($params['type']) && !empty($params['type'])) {
            $conditions[] = ['type', '=', $params['type']];
        } else {
            $conditions = [
                ['type', '<>', XsLoginRegisterWhitelist::WHITE_TYPE]
            ];
        }

        if (isset($params['object_id']) && !empty($params['object_id'])) {
            $conditions[] = ['object_id', 'like', "%{$params['object_id']}%"];
        }

        return $conditions;
    }
}