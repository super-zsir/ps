DROP TABLE IF EXISTS `nocode_schema_config`;

CREATE TABLE `nocode_schema_config` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `schema_json` mediumtext COMMENT 'schema config',
  `ncid` varchar(64) NOT NULL DEFAULT '' COMMENT 'ncid',
  `system_id` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '系统ID',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_ncid_system_id` (`ncid`,`system_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='nocode schema_json配置表';

DROP TABLE IF EXISTS `nocode_model_config`;

CREATE TABLE `nocode_model_config` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '资源映射标识',
  `table` varchar(100) NOT NULL DEFAULT '' COMMENT '表名',
  `model` varchar(255) NOT NULL DEFAULT '' COMMENT '映射表名',
  `master` varchar(20) NOT NULL DEFAULT '' COMMENT '主库',
  `slave` varchar(50) NOT NULL DEFAULT '' COMMENT '从库',
  `table_config` text NOT NULL COMMENT '表结构',
  `system_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '系统ID',
  `comment` varchar(50) NOT NULL DEFAULT '' COMMENT '描述',
  `allow_get` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '允许get',
  `allow_post` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '允许post',
  `allow_put` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '允许put',
  `allow_delete` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '允许delete',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_name_system_id` (`system_id`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='资源映射表';