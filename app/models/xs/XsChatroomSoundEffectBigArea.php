<?php

namespace Imee\Models\Xs;

class XsChatroomSoundEffectBigArea extends BaseModel
{
    protected static $primaryKey = 'id';

    public const SCHEMA_READ = 'xsserverslave';

    const STATUS_NORMAL = 1;
    const STATUS_DISABLE = 0;
    public static $statusMap = [
        self::STATUS_NORMAL  => '正常',
        self::STATUS_DISABLE => '禁用'
    ];


//CREATE TABLE `xs_chatroom_sound_effect_big_area`
//(
//`id`              int unsigned     NOT NULL AUTO_INCREMENT,
//`sound_effect_id` int unsigned     NOT NULL DEFAULT 0 COMMENT '音效ID',
//`big_area_id`     tinyint unsigned NOT NULL DEFAULT 0 COMMENT '大区ID',
//`status`          tinyint unsigned NOT NULL DEFAULT 0 COMMENT '状态 0禁用;1启用',
//`create_time`     bigint unsigned     NOT NULL DEFAULT 0 COMMENT '创建时间',
//`update_time`     bigint unsigned     NOT NULL DEFAULT 0 COMMENT '更新时间',
//PRIMARY KEY (`id`),
//KEY `idx_big_area_id` (`big_area_id`)
//) ENGINE = InnoDB
//DEFAULT CHARSET = utf8mb4 COMMENT ='房间背景音效关联大区表';
}