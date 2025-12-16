<?php

namespace Imee\Service\Operate\User;

use Imee\Comp\Operate\Auth\Models\Cms\CmsUser;
use Imee\Models\Xsst\XsstAdminWhitelist;
use Imee\Service\StatusService;

class MobileWhiteListService
{
    public function getListAndTotal(array $params): array
    {
        $limit = array_get($params, 'limit', 15);
        $page = array_get($params, 'page', 1);

        $adminUid = array_get($params, 'user_id', 0);

        $query = [['type', '=', XsstAdminWhitelist::TYPE_USER_MOBILE], ['deleted', '=', XsstAdminWhitelist::DELETE_NO]];
        $adminUid && $query[] = ['admin_uid', '=', $adminUid];

        $data = XsstAdminWhitelist::getListAndTotal($query, '*', 'id desc', $page, $limit);

        $admins = array_merge(array_column($data['data'], 'admin_uid'), array_column($data['data'], 'create_uid'));
        $admins = CmsUser::getAdminUserBatch($admins);

        foreach ($data['data'] as &$item) {
            $item['admin_name'] = empty($admins[$item['admin_uid']]['user_name']) ? '' : $admins[$item['admin_uid']]['user_name'];
            $item['create_name'] = empty($admins[$item['create_uid']]['user_name']) ? '' : $admins[$item['create_uid']]['user_name'];

            $item['create_time'] = $item['create_time'] ? date('Y-m-d H:i:s', $item['create_time']) : $item['create_time'];
        }

        return $data;
    }

    public function add(array $params): array
    {
        $adminUid = (int)array_get($params, 'user_id', 0);
        $adminId = (int)array_get($params, 'admin_id', 0);


        $whitelist = XsstAdminWhitelist::findOneByWhere([
            ['type', '=', XsstAdminWhitelist::TYPE_USER_MOBILE],
            ['admin_uid', '=', $adminUid],
            ['deleted', '=', XsstAdminWhitelist::DELETE_NO]
        ]);
        if ($whitelist) {
            return [false, '该用户已存在'];
        }

        $data = [
            'type'        => XsstAdminWhitelist::TYPE_USER_MOBILE,
            'deleted'     => XsstAdminWhitelist::DELETE_NO,
            'admin_uid'   => $adminUid,
            'create_uid'  => $adminId,
            'create_time' => time(),
        ];

        list($flg, $rec) = XsstAdminWhitelist::add($data);
        return [$flg, $flg ? ['id' => $rec, 'after_json' => $data] : $rec];
    }

    public function delete(array $params): array
    {
        $id = (int)array_get($params, 'id', 0);
        $model = XsstAdminWhitelist::findOne($id);
        if (empty($model)) {
            return [false, '数据不存在'];
        }

        $update = [
            'type'        => XsstAdminWhitelist::TYPE_USER_MOBILE,
            'deleted'     => XsstAdminWhitelist::DELETE_YES,
            'update_time' => time()
        ];
        list($flg, $rec) = XsstAdminWhitelist::edit($id, $update);

        return [$flg, $flg ? ['id' => $rec, 'before_json' => $model, 'after_json' => array_merge($model, $update)] : $rec];
    }

    public static function getUserMap($value = null, string $format = '')
    {
        $lists = CmsUser::findAll();
        $map = [];
        foreach ($lists as $v) {
            $map[$v["user_id"]] = sprintf("%s - %s", $v["user_id"], $v['user_name']);
        }

        if (!empty($value)) {
            return $map[$value] ?? '';
        }
        if (!empty($format)) {
            $map = StatusService::formatMap($map, $format);
        }
        return $map;
    }

}