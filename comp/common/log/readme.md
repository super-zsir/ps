# 操作日志记录

## controller文件里，action添加日志注释logRecord，自动记录操作日志到公共日志表里

## 示例

### content操作内容模版；action 0 新增 1编辑 2删除 3审核； model 表名； model_id 主键id字段

```PHP
    /**
     * @logRecord(content = "后台用户修改", action = "2", model = "cms_user", model_id = "user_id")
     */
    public function modifyAction()
    {
    
     //如果需要记录修改前修改后before_json after_json
     return $this->outputSuccess(['before_json'=>[], 'after_json'=>[]]);
    }
```

## 日志表结构

### 日志表根据业务自定义名称 bms_operate_log bms_gaia_operate_log bms_broker_operate_log

### 把 doc/BmsOperateLog表拷贝到项目models/xsst下

```SQL
CREATE TABLE `bms_operate_log`
(
    `id`           bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
    `uid`          bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
    `model_id`     bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '来源表ID',
    `model`        varchar(100) NOT NULL DEFAULT '' COMMENT '来源表',
    `action`       int(2) unsigned NOT NULL DEFAULT '0' COMMENT '动作：0-新增1-修改2-删除',
    `type`         int(2) unsigned NOT NULL DEFAULT '0' COMMENT '日志类型：0-后台操作1-cli日志',
    `content`      varchar(255) NOT NULL DEFAULT '' COMMENT '操作内容',
    `before_json`  text COMMENT '更改前内容json',
    `after_json`   text COMMENT '更改后内容json',
    `operate_id`   bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '操作人ID',
    `operate_name` varchar(50)  NOT NULL DEFAULT '' COMMENT '操作人',
    `created_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
    `updated_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY            `idx_uid` (`uid`),
    KEY            `idx_model_id` (`model_id`),
    KEY            `idx_model` (`model`),
    KEY            `idx_operate_id` (`operate_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='运营系统操作日志';

CREATE TABLE `bms_error_log`
(
    `id`            int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
    `module_name`   varchar(32)  NOT NULL DEFAULT '' COMMENT '模块名称',
    `path`          varchar(128) NOT NULL DEFAULT '' COMMENT '操作地址',
    `action`        varchar(64)  NOT NULL DEFAULT '' COMMENT '操作方法',
    `request_param` longtext COMMENT '请求参数',
    `message`       text COMMENT '错误信息',
    `admin_id`      int(11) unsigned NOT NULL DEFAULT '0' COMMENT '后台用户id',
    `admin_name`    varchar(63)  NOT NULL DEFAULT '' COMMENT '后台用户名称',
    `dateline`      int(11) unsigned NOT NULL DEFAULT '0' COMMENT '操作时间',
    PRIMARY KEY (`id`),
    KEY             `idx_dateline` (`dateline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='错误日志';

CREATE TABLE `bms_login_log`
(
    `id`         int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
    `type`       tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '操作类型，1:登录,2:退出',
    `browser`    text COMMENT '浏览器信息',
    `ip`         int(11) unsigned NOT NULL DEFAULT '0' COMMENT '登录ip',
    `admin_id`   int(11) unsigned NOT NULL DEFAULT '0' COMMENT '后台用户id',
    `admin_name` varchar(63) NOT NULL DEFAULT '' COMMENT '后台用户名称',
    `dateline`   int(11) unsigned NOT NULL DEFAULT '0' COMMENT '操作时间',
    PRIMARY KEY (`id`),
    KEY          `idx_admin_name` (`admin_name`),
    KEY          `idx_ip` (`ip`),
    KEY          `idx_dateline` (`dateline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='登录日志';
```

## 操作日志界面

### 导入低代码配置

```
拷贝doc/initData_operatelog.json 到 项目public/tmp目录下
创建系统模版 - 日志管理
在 日志管理 菜单编辑 填写 comp/common/log/src/controller
最后 添加 操作日志 页面。
```

# 如果使用sentry预警

## 需要安装 composer require sentry/sdk 2.0.3

## 巡检2.0

1. 新增通知配置功能
2. 日志提示规范化
3. 操作日志由各自系统中移动至组件里

#### 更新DB

```sql
use xsst;

DROP TABLE IF EXISTS `xsst_notice_group_config`;
CREATE TABLE `xsst_notice_group_config`
(
    `id`       int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
    `name`     varchar(128) NOT NULL DEFAULT '' COMMENT '通知群名称',
    `webhook`  varchar(128) NOT NULL DEFAULT '0' COMMENT 'webhook',
    `admin`    int(10) unsigned NOT NULL DEFAULT '0' COMMENT '操作人id',
    `dateline` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '操作时间',
    `status`   tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态：0:生效,1:失效',
    PRIMARY KEY (`id`),
    KEY        `idx_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='通知群配置';

DROP TABLE IF EXISTS `xsst_notice_config`;
CREATE TABLE `xsst_notice_config`
(
    `id`          int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
    `gid`         int(10) unsigned NOT NULL DEFAULT '0' COMMENT '通知群id',
    `name`        varchar(128) NOT NULL DEFAULT '' COMMENT '通知名称',
    `mid`         int(10) unsigned NOT NULL DEFAULT '0' COMMENT '功能id',
    `action`      text COMMENT '具体操作类型json',
    `admin`       int(10) unsigned NOT NULL DEFAULT '0' COMMENT '操作人id',
    `dateline`    int(10) unsigned NOT NULL DEFAULT '0' COMMENT '操作时间',
    `status`      tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态：0:生效,1:失效',
    PRIMARY KEY (`id`),
    KEY           `idx_name` (`name`),
    KEY           `idx_gid` (`gid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='通知配置';

DROP TABLE IF EXISTS `xsst_notice_log`;
CREATE TABLE `xsst_notice_log`
(
    `id`       int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
    `nid`      int(10) unsigned NOT NULL DEFAULT '0' COMMENT '通知id',
    `path`     varchar(255) NOT NULL DEFAULT '' COMMENT '访问模块',
    `params`   text COMMENT '请求参数',
    `status`   tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态：0:待发送,1:已发送',
    `admin`    int(10) unsigned NOT NULL DEFAULT '0' COMMENT '操作人id',
    `dateline` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '操作时间',
    PRIMARY KEY (`id`),
    KEY        `idx_nid` (`nid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='通知日志';

ALTER TABLE bms_error_log
    ADD COLUMN `status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '状态：0:已发送,1:待发送';
```

#### 涉及guid

```
noticegroupconfig  通知群配置
noticeconfig       通知配置 
noticelog          通知记录
operatelog         操作日志
```

#### 项目内调整

```php
// 新增文件：
server/app/helper/contstant/LogContstant.php，并定义LOG_MODEL_MAP，维护模块名称和model关系

// 修改ImeeApplication部分代码
echo $resp = $application->handle()->getContent();
fastcgi_finish_request();
//记录操作日志
$get = $application->request->getQuery();
$post = $application->request->getPost();
$resp = json_decode($resp, true);

if (!$resp['success']) {
    return;
}
$admin = Helper::getSystemUserInfo();
$admin = ['operate_id' => $admin['user_id'], 'operate_name' => $admin['user_name']];
$request = array_merge($get, $post, $admin);
if ($di->get('logRecordInfo')) {
    OperateLog::addLog($di->get('logRecordInfo'), $request, $resp);
}
// 记录通知日志
NoticeService::addNoticeLog($request);

// comp.php
[common-log-web]
git_remote="git@github.com:olaola-chat/bms-comp-web.git"
pull_path="log"
save_path="../web/src/pages/log"
version=""


// web/src/pages/Modal/index.js 新增下面代码
...
import {logModal} from '../log';


export const modalConfig = [
    ...logModal
];


```

#### cli

```
*/10 * * * * php cli.php notice -process 1  发送通知消息
*/10 * * * * php cli.php notice -process 2  发送错误消息
```

#### 更新comp
```
php cli.php comp update common-log
php cli.php comp update common-log-web
```
