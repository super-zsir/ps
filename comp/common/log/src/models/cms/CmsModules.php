<?php

namespace Imee\Comp\Common\Log\Models\Cms;

use Imee\Service\Helper;

class CmsModules extends BaseModel
{
    public static $primaryKey = 'module_id';

    const M_TYPE_MENU = 1;
    const M_TYPE_PAGE = 2;

    const IS_ACTION_YES = 1;
    const IS_ACTION_NO = 0;

    const DELETED_YES = 1;
    const DELETED_NO = 0;

    /**
     * 获取菜单名称
     * @param array $moduleIds
     * @return array
     */
    public static function getModuleNameByModuleIds(array $moduleIds): array
    {
        $moduleList = CmsModules::getListByWhere([
            ['module_id', 'IN', $moduleIds],
            ['system_id', '=', SYSTEM_ID]
        ], 'module_id, module_name');

        return $moduleList ? array_column($moduleList, 'module_name', 'module_id') : [];
    }

    /**
     * 根据pid获取菜单名称
     * @param int $pid
     * @return array
     */
    public static function getModuleNameByPid(int $pid): array
    {
        $moduleList = CmsModules::getListByWhere([
            ['parent_module_id', '=', $pid],
            ['system_id', '=', SYSTEM_ID]
        ], 'module_name, controller, action');

        if (empty($moduleList)) {
            return [];
        }

        $nameMap = [];
        foreach ($moduleList as $module) {
            $key = $module['controller'] . '.' . $module['action'];
            $nameMap[$key] = $module['module_name'];
        }

        return $nameMap;
    }

    /**
     * 根据controller和action获取菜单名称
     * @param string $controller
     * @param string $action
     * @return string
     */
    public static function getModuleNameByControllerAndAction(string $controller, string $action): string
    {
        $module = CmsModules::findOneByWhere([
            ['controller', '=', $controller],
            ['action', '=', $action],
            ['system_id', '=', SYSTEM_ID]
        ]);

        if (empty($module)) {
            return '';
        }

        $parentModule = CmsModules::findOneByWhere([
            ['module_id', '=', $module['parent_module_id']],
            ['system_id', '=', SYSTEM_ID]
        ]);

        $moduleName = $module['module_name'];
        if ($parentModule) {
            $moduleName = $parentModule['module_name'] . '-' . $moduleName;
        }

        return $moduleName;
    }
}