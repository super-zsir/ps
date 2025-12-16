<?php

namespace Imee\Models\Xs;

class XsUserWealthLvHideLog extends BaseModel
{
    protected static $primaryKey = 'id';

    public const SCHEMA_READ = 'xsserverslave';

    /**
     * 字段定义
     */
    protected static $fields = [
        'id',           // 主键id
        'task_id',      // 下发任务id
        'uid',          // 用户ID
        'days',         // 发放天数
        'remark',       // 备注
        'operator',     // 操作人
        'create_time',  // 下发时间
    ];

    public static function tableName()
    {
        return 'xs_user_wealth_lv_hide_log';
    }

    public static function uploadFields(): array
    {
        return [
            'uid' => '用户ID',
            'days' => '发放天数',
            'remark' => '备注',
            'operator' => '操作人',
        ];
    }
}
