<?php

namespace Imee\Models\Xs;

class XsCustomImLog extends BaseModel
{
    protected static $primaryKey = 'id';

    const MSG_TYPE_GUILD_GIFT = 0;
    const MSG_TYPE_RECHARGE_LEVEL_1 = 1;
    const MSG_TYPE_RECHARGE_LEVEL_2 = 2;
    const MSG_TYPE_RECHARGE_LEVEL_3 = 3;

    public static $msgTypeMap = [
        self::MSG_TYPE_GUILD_GIFT => '工会收礼前x名',
        self::MSG_TYPE_RECHARGE_LEVEL_1 => '充值达标等级1',
        self::MSG_TYPE_RECHARGE_LEVEL_2 => '充值达标等级2',
        self::MSG_TYPE_RECHARGE_LEVEL_3 => '充值达标等级3',
    ];

//CREATE TABLE `xs_custom_im_log` (
//`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
//`uid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
//`big_area` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '用户大区ID',
//`msg` text COMMENT '消息内容',
//`cycle` varchar(30) DEFAULT NULL COMMENT '周期',
//`msg_type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '类型 0.工会收礼前x名 1.充值达标等级1 2.充值达标等级2 3.充值达标等级3',
//`dateline` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
//PRIMARY KEY (`id`),
//KEY `idx_uid` (`uid`),
//KEY `idx_dateline` (`dateline`)
//) ENGINE=InnoDB   DEFAULT CHARSET=latin1 COMMENT='定制im发送记录表';


}