<?php

namespace Imee\Models\Xsst;


class XsstIssuedRoomStealthPrivilege extends BaseModel
{
    protected static $primaryKey = 'id';

    /**
     * 任务状态
     */
    const STATE_PENDING = 0;
    const STATE_SUCCESS = 1;
    const STATE_FAIL = 2;
    public static $stateMap = [
        self::STATE_PENDING => '待处理',
        self::STATE_SUCCESS => '成功',
        self::STATE_FAIL => '失败',
    ];

    /**
     * 字段定义
     */
    protected static $fields = [
        'id',           // 任务id
        'num',          // 发放人次
        'days',         // 权益天数
        'remark',       // 备注
        'create_time',  // 创建时间
        'admin_id',     // 创建人id
        'state',        // 任务状态
        'send_time',    // 发放时间
    ];
} 