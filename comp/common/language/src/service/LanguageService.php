<?php

namespace Imee\Comp\Common\Language\Service;

use Dcat\EasyExcel\Excel;
use Imee\Comp\Common\Log\Service\OperateLog;
use Imee\Comp\Operate\Auth\Models\Cms\CmsModules;
use Imee\Exception\ApiException;
use Imee\Service\Helper;
use Imee\Service\Lesscode\Context\GuidContext;
use Imee\Service\Lesscode\MenuService;
use Imee\Service\Lesscode\Schema\SchemaService;
use Phalcon\Di;
use Imee\Comp\Common\Language\Models\Xsst\XsstMultiLanguageTranslate;

/**
 * 多语言服务
 */
class LanguageService
{
    /**
     * 获取页面列表
     * @param array $params
     * @return array
     */
    public static function getPageAndMenuList(array $params): array
    {
        $conditions = [];

        $moduleName = $params['module_name'] ?? '';
        $mType = $params['m_type'] ?? 0;
        $moduleName && $conditions[] = ['module_name', 'like', $moduleName];
        if ($mType == CmsModules::M_TYPE_MENU) {
            $params['page'] = 0;
            $params['limit'] = 0;
            $conditions[] = ['m_type', 'IN', [CmsModules::M_TYPE_PAGE, CmsModules::M_TYPE_MENU]];
        } else {
            $conditions[] = ['m_type', '=', $mType];
        }
        $list = CmsModules::getPageAndMenuListAndTotal($conditions, $params['page'], $params['limit']);
        $translateData = XsstMultiLanguageTranslate::getTranslateByMid(0);
        // 收集所有需要查询的模块ID
        $moduleIds = Helper::arrayFilter($list['data'], 'module_id');
        $logs = OperateLog::getFirstLogListMapping('multilanguagemanage', $moduleIds);
        // 批量查询所有父模块信息
        $parentModules = [];
        CmsModules::getParentModuleByModuleIds($parentModules, $moduleIds);

        foreach ($list['data'] as &$val) {
            $parentModule = $parentModules[$val['module_id']] ?? [];
            $parentModuleName = implode('/', array_column($parentModule, 'module_name'));
            $parentModuleName && $val['page_path'] = $parentModuleName;
            $val['dateline'] = isset($logs[$val['module_id']]['created_time']) ? Helper::now($logs[$val['module_id']]['created_time']) : '';
            $val['operator'] = $logs[$val['module_id']]['operate_name'] ?? '';
        }
        $list['data'] = self::formatFieldsList($list['data'], 'module_name', $translateData);

        return $list;
    }

    /**
     * 获取字段列表信息
     * @param array $params
     * @return array
     * @throws ApiException
     */
    public static function getFieldsList(array $params): array
    {
        $moduleId = intval($params['module_id'] ?? 0);
        if (empty($moduleId)) {
            throw new ApiException(ApiException::MSG_ERROR, '请选择模块');
        }

        // 如果存在先取出表中的数据
        $fields = self::getExistsFields($moduleId);
        if ($fields) {
            return $fields;
        }

        // 不存在则取出低代码相关的数据
        $modules = CmsModules::findOne($moduleId);
        if (empty($modules)) {
            throw new ApiException(ApiException::MSG_ERROR, '模块不存在');
        }

        // 从controller中取出guid
        $controller = explode('/', $modules['controller']);
        $guid = array_pop($controller);

        return self::getLessCodeFields($guid);
    }

    /**
     * 获取已存在的字段
     * @param int $mid
     * @return array
     */
    public static function getExistsFields(int $mid): array
    {
        $translate = XsstMultiLanguageTranslate::getTranslateByMid($mid);
        if ($translate) {
            $data = [];
            foreach ($translate as $lang => $item) {
                foreach ($item as $k => $v) {
                    $data[$k]['name'] = $k;
                    $data[$k]['name_' . $lang] = $v;
                }
            }
            return array_values($data);
        }

        return [];
    }

    /**
     * 低代码回显字段获取
     * @param string $guid
     * @return array
     */
    public static function getLessCodeFields(string $guid): array
    {
        $schemaService = new SchemaService();
        $menuService = new MenuService();
        $context = new GuidContext(['guid' => $guid]);
        // 低代码不存在直接返回[]即可
        if ($menuService->checkCreate($context)) {
            return [];
        }
        $fields = $schemaService->getFields($context);
        foreach ($fields as &$item) {
            // 转一下，前端统一处理的字段name
            $item['name'] = $item['comment'];
        }

        return self::formatFieldsList($fields);
    }

    /**
     * 格式化列表
     * @param array $list
     * @param string $prefix
     * @param array $translateData
     * @return array
     */
    private static function formatFieldsList(array $list, string $prefix = 'name', array $translateData = []): array
    {
        foreach ($list as &$item) {
            foreach (self::getLanguageArray() as $lang) {
                $item[$prefix . '_' . $lang] = $translateData[$lang][$item[$prefix]] ?? '';
            }
        }

        return $list;
    }

    /**
     * 处理翻译入口
     * @param $data
     * @param $params
     * @return array
     */
    public static function translateData($data, $params): array
    {
        $guid = $params['guid'] ?? '';
        $moduleId = $params['parent_module_id'] ?? $params['module_id'] ?? 0;
        if ($guid) {
            return self::translateLessCodeOutput($guid, $data, $params['lang']);
        } else {
            return self::translateManualOutput($moduleId, $data, $params['lang']);
        }
    }

    /**
     * 导入翻译数据
     * @param array $params
     * @return array
     * @throws ApiException
     * @throws \OpenSpout\Common\Exception\IOException
     * @throws \OpenSpout\Common\Exception\UnsupportedTypeException
     */
    public static function import(array $params): array
    {
        $moduleId = intval($params['module_id'] ?? 0);
        if (empty($moduleId)) {
            throw new ApiException(ApiException::MSG_ERROR, '请选择模块');
        }

        $request = Di::getDefault()->get('request');
        if (!$request->hasFiles()) {
            throw new ApiException(ApiException::MSG_ERROR, '没有上传文件');
        }

        $files = $request->getUploadedFiles();
        $file = $files[0];
        $fileTempName = $file->getTempName();
        $savePath = '/tmp/' . $file->getName();
        move_uploaded_file($fileTempName, $savePath);
        $allSheets = Excel::import($savePath)->toArray();
        @unlink($savePath);

        return XsstMultiLanguageTranslate::addData($moduleId, self::handleTranslateJson($moduleId, $allSheets));
    }

    /**
     * 获取翻译数据
     * @param array $params
     * @return array
     * @throws ApiException
     */
    public static function getTranslateData(array $params): array
    {
        $controller = $params['controller'] ?? '';
        if (empty($controller)) {
            throw new ApiException(ApiException::MSG_ERROR, '请选择模块');
        }

        $modules = CmsModules::getInfoByController($controller);
        if (empty($modules)) {
            return [];
        }

        return XsstMultiLanguageTranslate::getModulesLanguage($modules['module_id'], $params['lang']);
    }

    /**
     * 保存翻译数据
     * @param array $params
     * @param bool $isMenu
     * @return array
     * @throws ApiException
     */
    public static function setTranslationData(array $params, bool $isMenu = false): array
    {
        // 菜单单独维护一条数据
        // module_id 设置 菜单时默认为0
        $moduleId = $isMenu ? 0 : intval($params['module_id']);
        $translateJson = $params['translate_json'] ?? '';
        self::validationTranslateJson($translateJson);

        if ($isMenu) {
            $params['translate_json'] = self::handleMenuTranslateJson($translateJson);
        }

        return XsstMultiLanguageTranslate::addData($moduleId, $params['translate_json']);
    }

    /**
     * 验证翻译json
     * @param string $translateJson
     * @throws \Imee\Exception\ApiException
     * @return void
     */
    public static function validationTranslateJson(string $translateJson): void
    {
        $translateData = json_decode($translateJson, true);
        if (empty($translateData)) {
            throw new ApiException(ApiException::MSG_ERROR, '翻译数据有误，请检查');
        }

        foreach ($translateData as $item) {
            foreach ($item as $k => $v) {
                if (empty($k)) {
                    throw new ApiException(ApiException::MSG_ERROR, '翻译数据有误，请检查');
                }
            }
        }
    }


    public static function handleMenuTranslateJson(string $translateJson): string
    {
        $translateData = json_decode($translateJson, true);
        $newTranslateData = [];
        foreach ($translateData as $lang => $item) {
            $langTranslate = [];
            foreach ($item as $k => $v) {
                if (str_contains($k, '/')) {
                    $moduleName = explode('/', $k);
                    $key = array_pop($moduleName);
                    $langTranslate[$key] = $v;
                } else {
                    $langTranslate[$k] = $v;
                }
            }
            $newTranslateData[$lang] = $langTranslate;
        }

        return self::jsonEncode($newTranslateData);
    }

    /**
     * 处理翻译json
     * @param int $moduleId
     * @param array $allSheets
     * @return string
     * @throws ApiException
     */
    private static function handleTranslateJson(int $moduleId, array $allSheets): string
    {
        if (empty($allSheets)) {
            throw new ApiException(ApiException::MSG_ERROR, '没有数据');
        }

        $languageTranslateData = XsstMultiLanguageTranslate::getTranslateByMid($moduleId);

        // 文件固定格式 field => key, translate => value
        foreach ($allSheets as $sheetIndex => $sheetData) {
            foreach ($sheetData as $row) {
                // 如果没有key或者value，跳过
                if (empty($row['key']) || empty($row['value'])) {
                    continue;
                }
                $languageTranslateData[$sheetIndex][$row['key']] = $row['value'];
            }
        }
        return self::jsonEncode($languageTranslateData);
    }

    /**
     * json 编码
     * @param array $array
     * @return bool|string
     */
    public static function jsonEncode(array $array)
    {
        return json_encode($array, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * 手写页面翻译
     * @param int $mid
     * @param array $output
     * @param string $language
     * @return array
     */
    public static function translateManualOutput(int $mid, array $output, string $language): array
    {
        $translateData = XsstMultiLanguageTranslate::getModulesLanguage($mid, $language);
        return self::translateOutput($output, $translateData);
    }

    /**
     * 低代码翻译
     * @param string $guid
     * @param array $output
     * @param string $language
     * @return array
     */
    public static function translateLessCodeOutput(string $guid, array $output, string $language): array
    {
        // 这个地方不需要判断低代码是否存在
        $menuService = new MenuService();
        $context = new GuidContext(['guid' => $guid]);
        $data = $menuService->getInfo($context);

        if (empty($data)) {
            return $output;
        }
        $translateData = XsstMultiLanguageTranslate::getModulesLanguage($data['module_id'], $language);

        return self::translateOutput($output, $translateData);
    }

    /**
     * 处理翻译
     * @param array $output
     * @param array $translateData
     * @return array
     */
    public static function translateOutput(array $output, array $translateData): array
    {
        if (!empty($output)) {
            foreach ($output as $key => $item) {
                if (is_array($item)) {
                    $output[$key] = self::translateOutput($item, $translateData);
                } else if (is_string($item) && !empty($translateData[$item])) {
                    $output[$key] = $translateData[$item];
                }
            }
        }
        return $output;
    }

    /**
     * 获取系统多语言枚举
     * 中文自带，不需要翻译
     * @return string[]
     */
    public static function getLanguageArray(): array
    {
        // 从各个项目中去获取支持的语言
        if (method_exists(Helper::class, 'getLanguageArray')) {
            return Helper::getLanguageArray();
        }

        return ['en'];
    }
}