<?php

namespace Imee\Controller\Nocode;


use Imee\Comp\Nocode\Service\Logic\ModelLogic;
use Imee\Controller\BaseController;

/**
 * 模型管理
 */
class ModelController extends AdminBaseController
{
    public function onConstruct()
    {
        parent::onConstruct();
    }

    /**
     * @page model
     * @name 模型管理
     */
    public function mainAction()
    {
    }

    /**
     * @page model
     * @point 列表
     */
    public function listAction()
    {
        $data = ModelLogic::getInstance()->getList($this->params);
        return $this->outputSuccess($data);
    }

    /**
     * @page resource
     * @point 详情
     */
    public function infoAction()
    {
        $data = ModelLogic::getInstance()->info($this->params);
        return $this->outputSuccess($data);
    }
}