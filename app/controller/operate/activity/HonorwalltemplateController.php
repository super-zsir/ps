<?php

namespace Imee\Controller\Operate\Activity;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Activity\Honorwalltemplate\AddValidation;
use Imee\Controller\Validation\Operate\Activity\Honorwalltemplate\EditValidation;
use Imee\Service\Operate\Activity\HonorWallTemplateService;

class HonorwalltemplateController extends BaseController
{
    /**
     * @var HonorWallTemplateService $service
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new HonorWallTemplateService();
    }
    
    /**
     * @page honorwalltemplate
     * @name 荣誉墙模版
     */
    public function mainAction()
    {
    }
    
    /**
     * @page honorwalltemplate
     * @point 列表
     */
    public function listAction()
    {
        $c = $this->params['c'] ?? '';
        if ($c == 'options') {
            return $this->outputSuccess($this->service->getOptions());
        } else if ($c == 'getButtonList') {
            return $this->outputSuccess($this->service->getButtonListMap($this->params['act_id']));
        }
        $list = $this->service->getListAndTotal($this->params);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }
    
    /**
     * @page honorwalltemplate
     * @point 创建
     * @logRecord(content = '创建', action = '0', model = 'honorwalltemplate', model_id = 'id')
     */
    public function createAction()
    {
        $this->service->formatParams($this->params);
        AddValidation::make()->validators($this->params);
        $data = $this->service->create($this->params);
        return $this->outputSuccess($data);
    }
    
    /**
     * @page honorwalltemplate
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'honorwalltemplate', model_id = 'id')
     */
    public function modifyAction()
    {
        $this->service->formatParams($this->params);
        EditValidation::make()->validators($this->params);
        $data = $this->service->modify($this->params);
        return $this->outputSuccess($data);
    }
    
    /**
     * @page honorwalltemplate
     * @point 删除
     * @logRecord(content = '删除', action = '2', model = 'honorwalltemplate', model_id = 'id')
     */
    public function deleteAction()
    {
        $data = $this->service->delete($this->params);
        return $this->outputSuccess($data);
    }

    /**
     * @page honorwalltemplate
     * @point 复制
     * @logRecord(content = '复制', action = '0', model = 'honorwalltemplate', model_id = 'id')
     */
    public function copyAction()
    {
        $data = $this->service->copy($this->params);
        return $this->outputSuccess($data);
    }

    /**
     * @page honorwalltemplate
     * @point 详情
     */
    public function infoAction()
    {
        $data = $this->service->info($this->params);
        return $this->outputSuccess($data);
    }
}