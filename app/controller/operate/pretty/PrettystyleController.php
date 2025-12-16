<?php

namespace Imee\Controller\Operate\Pretty;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Pretty\Style\ListValidation;
use Imee\Controller\Validation\Operate\Pretty\Style\CreateValidation;
use Imee\Controller\Validation\Operate\Pretty\Style\ModifyValidation;
use Imee\Service\Domain\Service\Pretty\PrettystyleService;

class PrettystyleController extends BaseController
{
    /**
     * @var PrettystyleService $service
     */
    private $service;
    public function onConstruct()
    {
        parent::onConstruct();
        
        $this->service = new PrettystyleService;
    }

    /**
     * @page prettystyle
     * @name -自选靓号类型
     */
    public function mainAction()
    {
    }

    /**
     * @page prettystyle
     * @point 列表
     */
    public function listAction()
    {
        $params = $this->trimParams($this->params);
        ListValidation::make()->validators($params);
        $res = $this->service->getList($params);
        return $this->outputSuccess($res['data'], array('total' => $res['total']));
    }

    /**
     * @page prettystyle
     * @point 创建
     */
    public function createAction()
    {
        $params = $this->trimParams($this->params);
        CreateValidation::make()->validators($params);
        $this->service->create($params);
        return $this->outputSuccess();
    }

    /**
     * @page prettystyle
     * @point 修改
     */
    public function modifyAction()
    {
        $params = $this->trimParams($this->params);
        ModifyValidation::make()->validators($params);
        $this->service->modify($params);
        return $this->outputSuccess();
    }

    /**
     * @page prettystyle
     * @point 修改状态
     * @logRecord(content = '修改状态', action = '1', model = 'xs_customize_pretty_style', model_id = 'id')
     */
    public function disableAction()
    {
        $params = $this->trimParams($this->params);
        $this->service->disable($params);
        return $this->outputSuccess();
    }
}
