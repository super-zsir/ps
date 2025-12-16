<?php

namespace Imee\Models\Xs;

class XsChatroomSoundEffect extends BaseModel
{
    protected static $primaryKey = 'id';

    public const SCHEMA_READ = 'xsserverslave';

    const STATUS_NORMAL = 1;
    const STATUS_DISABLE = 0;
    public static $statusMap = [
        self::STATUS_NORMAL  => '正常',
        self::STATUS_DISABLE => '禁用'
    ];

//-- name_json: '{"en":"kiss","cn":"亲吻"}'
//CREATE TABLE `xs_chatroom_sound_effect`
//(
//`id`               int unsigned     NOT NULL AUTO_INCREMENT,
//`name_json`        varchar(512)     NOT NULL DEFAULT '' COMMENT '音效名(JSON)',
//`icon_url`         varchar(128)     NOT NULL DEFAULT '' COMMENT '音效图标url',
//`sound_url`        varchar(128)     NOT NULL DEFAULT '' COMMENT '音效文件url',
//`duration_seconds` tinyint unsigned NOT NULL DEFAULT 0 COMMENT '音效持续时长(单位:秒)',
//`effect_url`       varchar(128)     NOT NULL DEFAULT '' COMMENT '动效url',
//`status`           tinyint unsigned NOT NULL DEFAULT 0 COMMENT '状态 0禁用;1启用',
//`operator`         varchar(64)      NOT NULL DEFAULT '' COMMENT '操作人',
//`create_time`      int unsigned     NOT NULL DEFAULT 0 COMMENT '创建时间',
//`update_time`      int unsigned     NOT NULL DEFAULT 0 COMMENT '更新时间',
//PRIMARY KEY (`id`)
//) ENGINE = InnoDB
//DEFAULT CHARSET = utf8mb4 COMMENT ='房间音效配置表';

}