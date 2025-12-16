# 多语言配置

-   新增数据表

```sql
CREATE TABLE `xsst_multi_language_translate`
(
    `id`             int(11) unsigned NOT NULL AUTO_INCREMENT,
    `mid`            int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'cms_modules主键',
    `admin_id`       int(11) unsigned NOT NULL DEFAULT '0' COMMENT '操作人',
    `dateline`       int(11) unsigned NOT NULL DEFAULT '0' COMMENT '操作时间',
    `translate_json` text NOT NULL COMMENT '翻译json',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_mid` (`mid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='多语言翻译表'
```

## 项目里更新代码

-   BaseController.php

```php
    // 需要翻译转换的权限点
    protected $translatePermission = [
        'auth/menu.index',
        'auth/access.points',
        'lesscode/index.listConfig',
    ];

    protected $notPermissionCtl = [
        ...,
        'language/multilanguagemanage'
    ];

    public function beforeExecuteRoute()
    {
        $controller = $this->getController();
        ...
    }


    protected function getController()
    {
        $nameSpace = $this->dispatcher->getNamespaceName();
        $controller = $this->dispatcher->getControllerName();

        $preArr = explode('\\', trim(str_replace('Imee\Controller', '', $nameSpace), '\\'));
        $pre = implode('/', array_map(function ($val) {
            return lcfirst($val);
        }, $preArr));

        if (!empty($pre)) {
            $controller = $pre . '/' . $controller;
        }

        return $controller;
    }

    protected function outputJson($data)
    {
        $data = $this->translateOutput($data);
        ...
    }

    protected function translateOutput($data): array
    {
        $controller = $this->getController();
        $action = $this->dispatcher->getActionName();
        if (class_exists('Imee\Comp\Common\Language\Service\LanguageService')
            && in_array($controller . '.' . $action, $this->translatePermission)) {
            $data = LanguageService::translateData($data, $this->params);
        }

        return $data;
    }
```

-   Helper.php

```php
    // 多语言支持翻译语种
    public static function getLanguageArray(): array
    {
        return ['en', 'ar'];
    }

    // 新增 $isUnLink 属性
    public static function downLoadFile(string $filename, string $downloadName = '', string $ext = 'csv', bool $isUnLink = true)
    {
        ...
        $isUnLink && @unlink($filename);
        ...
    }

```

## 更新 CmsModules.php 代码（新版 Auth 可直接更新 Auth 组件）

```php

    /**
     * 获取所有页面权限
     * @param array $conditions
     * @param int $page
     * @param int $limit
     * @return array
     */
    public static function getPageAndMenuListAndTotal(array $conditions, int $page, int $limit): array
    {
        $conditions = array_merge($conditions, [
            ['is_action', '=', self::IS_ACTION_NO],
            ['deleted', '=', self::DELETED_NO],
            ['system_id', '=', SYSTEM_ID]
        ]);

        return self::getListAndTotal($conditions, '*', 'module_id desc', $page, $limit);
    }

    /**
     * 根据controller获取所有权限
     * @param string $controller
     * @return array
     */
    public static function getInfoByController(string $controller): array
    {
        $conditions = [
            ['controller', '=', $controller],
            ['is_action', '=', self::IS_ACTION_NO],
            ['deleted', '=', self::DELETED_NO],
            ['system_id', '=', SYSTEM_ID]
        ];

        return self::findOneByWhere($conditions);
    }

```

## 更新 Lesscode(不想更新的话，可以只复制代码，不更新组件)

-   更新文件 lesscode/service/ExportService.php

```php

    // 新增language参数
    public static function getListFields($guid, $language = 'zh_cn')
    {
        ...

        // 多语言处理
        if (class_exists('Imee\Comp\Common\Language\Service\LanguageService')) {
            $header = LanguageService::translateLessCodeOutput($guid, $header, $language);
        }

        return $header;
    }

```

## 去除之前菜单翻译

```php
    // path server/comp/operate/auth/service/processes/module/FilterProcess.php
    $menu['text'] = __T($menu['module_name'], [], $lang);
    // 改成
    $menu['text'] = $menu['module_name'];
```

## 更新组件 Language

-   comp.ini 新增 language 组件

```ini
[common-language]
git_remote="git@github.com:olaola-chat/bms-comp-common.git"
pull_path="language"
save_path="comp/common/language"
version=""
```

-   执行 cmd

```bash
php comp.php update common-language
```

-   comp 目录下 loader.php、router.php 文件 引入

```php
require_once ROOT . '/comp/common/language/app/loader.php';
require_once ROOT . '/comp/common/language/app/routes.php';
```

## 更新前端代码

-   web/src/pages/Modal/index.js

```js
    import LanguageSet from "./LanguageSet";
    import MenuSet from "./MenuSet";

    export const modalConfig = [
        ...,
        { id: "language_set", modal: LanguageSet },
        { id: "menu_set", modal: MenuSet },
    ]
```

-   新增文件 MenuSet.jsx LanguageSet.jsx

## [分支参考](https://github.com/olaola-chat/bms-partystar-admin-new/pull/579)

# 2.0

## 新增 system_id 参数

```sql
ALTER TABLE xsst_multi_language_translate ADD COLUMN `system_id` TINYINT(1) unsigned NOT NULL DEFAULT '3' COMMENT '系统ID';

ALTER TABLE `xsst_multi_language_translate` DROP INDEX `uk_mid`;

ALTER TABLE `xsst_multi_language_translate` ADD UNIQUE KEY `uk_mid_system_id` (`mid`, `system_id`);
```
