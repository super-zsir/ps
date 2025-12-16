CREATE TABLE `xsst_multi_language_translate`
(
    `id`             int(11) unsigned NOT NULL AUTO_INCREMENT,
    `mid`            int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'cms_modules主键',
    `admin_id`       int(11) unsigned NOT NULL DEFAULT '0' COMMENT '操作人',
    `dateline`       int(11) unsigned NOT NULL DEFAULT '0' COMMENT '操作时间',
    `translate_json` text NOT NULL COMMENT '翻译json',
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_mid` (`mid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='多语言翻译表'