<?php

namespace Imee\Controller\Language;


use Imee\Comp\Common\Language\Service\LanguageService;
use Imee\Comp\Operate\Auth\Models\Cms\CmsModules;
use Imee\Controller\BaseController;
use Imee\Service\Helper;

/**
 * 多语言翻译
 */
class MultilanguagemanageController extends BaseController
{
    public function onConstruct()
    {
        parent::onConstruct();
    }

    /**
     * @page multilanguagemanage
     * @name 多语言管理
     */
    public function mainAction()
    {
    }

    /**
     * @page  multilanguagemanage
     * @point 列表
     */
    public function listAction()
    {
        $c = $this->params['c'] ?? '';
        if ($c == 'getFields') {
            return $this->outputSuccess(LanguageService::getFieldsList($this->params));
        } else if ($c == 'tpl') {
            Helper::downLoadFile(ROOT . '/comp/common/language/doc/translate.xlsx', 'translate', 'xlsx', false);
        }

        $this->params['m_type'] = CmsModules::M_TYPE_PAGE;
        $list = LanguageService::getPageAndMenuList($this->params);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total']]);
    }

    /**
     * @page  multilanguagemanage
     * @point 导入多语言
     * @logRecord(content = '创建', action = '0', model = 'multilanguagemanage', model_id = 'module_id')
     */
    public function importAction()
    {
        $data = LanguageService::import($this->params);
        return $this->outputSuccess($data);
    }

    /**
     * @page  multilanguagemanage
     * @point 菜单列表
     */
    public function getMenuListAction()
    {
        $this->params['m_type'] = CmsModules::M_TYPE_MENU;
        $list = LanguageService::getPageAndMenuList($this->params);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total']]);
    }

    /**
     * @page  multilanguagemanage
     * @point 菜单翻译
     * @logRecord(content = '菜单翻译', action = '0', model = 'multilanguagemanage', model_id = 'module_id')
     */
    public function menuTranslationAction()
    {
        $data = LanguageService::setTranslationData($this->params, true);
        return $this->outputSuccess($data);
    }

    /**
     * @page  multilanguagemanage
     * @point 页面翻译
     * @logRecord(content = '页面翻译', action = '0', model = 'multilanguagemanage', model_id = 'module_id')
     */
    public function pageTranslationAction()
    {
        $data = LanguageService::setTranslationData($this->params);
        return $this->outputSuccess($data);
    }

    /**
     * @page  multilanguagemanage
     * @point 获取翻译数据
     */
    public function getTranslationAction()
    {
        $data = LanguageService::getTranslateData($this->params);
        return $this->outputSuccess($data);
    }
}