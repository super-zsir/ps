<?php

namespace Imee\Controller\Nocode;


use Imee\Comp\Nocode\Service\Logic\FormLogic;

/**
 * 表单管理
 */
class FormController extends AdminBaseController
{
    public function onConstruct()
    {
        parent::onConstruct();
    }

    /**
     * @page form
     * @name 表单管理
     */
    public function mainAction()
    {
    }

    /**
     * @page form
     * @point 列表
     */
    public function listAction()
    {
        $list = FormLogic::getInstance()->getList($this->params);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }

    /**
     * @page form
     * @point 保存
     */
    public function saveAction()
    {
        $data = FormLogic::getInstance()->save($this->params);
        return $this->outputSuccess($data);
    }

    /**
     * @page form
     * @point 详情
     */
    public function infoAction()
    {
        $data = FormLogic::getInstance()->info($this->params);
        return $this->outputSuccess($data);
    }

    /**
     * @page form
     * @point 删除
     */
    public function deleteAction()
    {
        $data = FormLogic::getInstance()->delete($this->params);
        return $this->outputSuccess($data);
    }

    /**
     * @page form
     * @point 校验
     */
    public function checkAction()
    {
        $data = FormLogic::getInstance()->check($this->params);
        return $this->outputSuccess($data);
    }
}