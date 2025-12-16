<?php


namespace Imee\Models\Xs;


class XsCircleReport extends BaseModel
{


    const STATUS_PENDING = 'pending';
    const STATUS_IGNORE = 'ignore';
    const STATUS_EMPTY = 'empty';


    public static $status = [
        self::STATUS_PENDING => '待处理',
        self::STATUS_IGNORE => '忽略',
        self::STATUS_EMPTY => '清空',
    ];


    const ROTYPE_TOPIC = 'topic';
    const ROTYPE_COMMENT = 'comment';

    public static $rotype = [
        self::ROTYPE_TOPIC => '朋友圈',
        self::ROTYPE_COMMENT => '评论',
    ];


}