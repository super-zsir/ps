<?php

namespace Imee\Models\Xsst;

class XsstFamilyConfigLog extends BaseModel
{
    const ACTION_CREATE = 'create';
    const ACTION_MODIFY = 'modify';
    const ACTION_DELETE = 'delete';
    const ACTION_DELETE_MEMBER = 'delete_member';
    const ACTION_CREATE_MEMBER = 'create_member';
    const ACTION_EDIT_ROLE = 'edit_role';

    public static $action = [
        self::ACTION_CREATE => '创建',
        self::ACTION_MODIFY => '修改',
        self::ACTION_DELETE => '删除',
        self::ACTION_EDIT_ROLE => '修改成员角色',
        self::ACTION_DELETE_MEMBER => '剔除成员',
        self::ACTION_CREATE_MEMBER => '添加成员'
    ];

    public static function getActionLog($fids, $action)
    {
        $list = self::getListByWhere([
            ['action', '=', $action],
            ['related_id', 'in', $fids]
        ]);
        if (empty($list)) return [];
        return array_column($list, 'op_uid', 'related_id');
    }

}