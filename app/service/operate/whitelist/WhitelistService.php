<?php

namespace Imee\Service\Operate\Whitelist;

use Imee\Comp\Operate\Auth\Models\Cms\CmsUser;
use Imee\Models\Xsst\BmsWhitelistSetting;
use Imee\Service\Helper;

class WhitelistService
{
    public function getList(array $params, int $page, int $pageSize): array
    {
        $conditions = $this->getConditions($params);
        $list = BmsWhitelistSetting::getListAndTotal($conditions, '*', 'id desc', $page, $pageSize);
        if ($list['total'] == 0) {
            return [];
        }
        foreach ($list['data'] as &$v) {
            $v['info'] = BmsWhitelistSetting::$table[$v['type']] . $v['value'];
            $users = CmsUser::getAdminUserBatch(explode(',', $v['uid']));
            $v['uid'] = explode(',', $v['uid']);
            $v['manager'] = implode(',', array_column($users, 'user_name'));
            $v['updated_at'] = date('Y-m-d H:i:s', $v['updated_at']);
        }
        return $list;
    }

    public function add(array $params): array
    {
        $data = [
            'name' => $params['name'],
            'type' => $params['type'],
            'description' => $params['description'],
            'value' => $params['value'],
            'uid' => implode(',', $params['uid']),
            'update_uid' =>  $params['admin_uid'],
            'update_uname' => Helper::getAdminName($params['admin_uid']),
            'updated_at' => time(),
        ];
        $errMsg = $this->validationWhiteList($data);
        if (!empty($errMsg)) {
            return [false, $errMsg];
        }
        return BmsWhitelistSetting::add($data);
    }

    public function edit(array $params): array
    {
        $data = [
            'id' => $params['id'],
            'name' => $params['name'],
            'type' => $params['type'],
            'description' => $params['description'],
            'value' => $params['value'],
            'uid' => implode(',', $params['uid']),
            'update_uid' =>  $params['admin_uid'],
            'update_uname' => Helper::getAdminName($params['admin_uid']),
            'updated_at' => time(),
        ];
        $errMsg = $this->validationWhiteList($data);
        if (!empty($errMsg)) {
            return [false, $errMsg];
        }
        return BmsWhitelistSetting::edit($params['id'], $data);
    }

    public function delete(int $id, int $adminUid)
    {
        $update = [
            'deleted' => 1,
            'update_uid' =>  $adminUid,
            'update_uname' => Helper::getAdminName($adminUid),
            'updated_at' => time(),
        ];
        return BmsWhitelistSetting::edit($id, $update);
    }

    public function validationWhiteList(array $data)
    {
        if (isset($data['id']) && $data['id'] > 0) {
            $rec = BmsWhitelistSetting::findFirst([
                "id <> {$data['id']} AND (name='{$data['name']}' OR (type = '{$data['type']}' AND value={$data['value']})) AND deleted=0"
            ]);
        } else {
            $rec = BmsWhitelistSetting::findFirst([
                "(name='{$data['name']}' OR (type = '{$data['type']}' AND value={$data['value']})) AND deleted=0"
            ]);
        }
        if ($rec) {
            if ($rec->name == $data['name']) {
                return '该白名单名称已存在';
            } else {
                return '该白名单类型值已存在';
            }
        }
        return '';
    }

    public function getConditions(array $params): array
    {
        $conditions = [
            ['deleted', '=', 0]
        ];
        if (!empty($params['name'])) {
            $conditions[] = ['name', 'LIKE', $params['name']];
        }
        if (!empty($params['type'])) {
            $conditions[] = ['type', '=', $params['type']];
        }
        return $conditions;
    }
}