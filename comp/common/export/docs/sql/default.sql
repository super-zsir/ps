use xsst;

CREATE TABLE `xsst_export_task`
(
    `id`            int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `op_uid`        int(10) unsigned NOT NULL DEFAULT '0' COMMENT '导出用户uid',
    `title`         varchar(100) NOT NULL DEFAULT '' COMMENT 'title',
    `file_name`     varchar(255) NOT NULL DEFAULT '' COMMENT '导出文件名:demo.csv',
    `file_url`      varchar(500) NOT NULL DEFAULT '' COMMENT '导出文件oss地址',
    `file_type`     varchar(10)  NOT NULL DEFAULT 'csv' COMMENT '导出文件类型',
    `project`       varchar(50)  NOT NULL COMMENT '项目名称',
    `export_params` text         NOT NULL COMMENT '导出参数',
    `remark`        varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
    `status`        tinyint(1) NOT NULL DEFAULT '0' COMMENT '导出状态，0:待导出，1:导出中，2:导出成功，3:导出失败',
    `created_at`    int(10) unsigned NOT NULL DEFAULT '0' COMMENT '导出创建时间',
    `completion_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '导出完成时间',
    `updated_at`    int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY             `idx_op_uid` (`op_uid`),
    KEY             `idx_project` (`project`),
    KEY             `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='导出任务表';
