<?php

namespace Imee\Controller\Operate;

use Imee\Controller\BaseController;
use Imee\Service\Operate\WelcomegiftbagService;
use Imee\Controller\Validation\Operate\Welcomegiftbag\CreateValidation;
use Imee\Controller\Validation\Operate\Welcomegiftbag\ModifyValidation;
use Imee\Controller\Validation\Operate\Welcomegiftbag\UnvalidValidation;

class WelcomegiftbagController extends BaseController
{
    /**
     * @var WelcomegiftbagService
     */
    private $service;
    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new WelcomegiftbagService();
    }

    /**
     * @page welcomegiftbag
     * @name 迎新礼包管理-礼包配置
     */
    public function mainAction()
    {
    }

    /**
     * @page welcomegiftbag
     * @point 列表
     */
    public function listAction()
    {
        $res = $this->service->getList($this->params);
        return $this->outputSuccess($res['data'] ?? [], ['total' => $res['total'] ?? 0]);
    }

    /**
     * @page welcomegiftbag
     * @point 创建
     * @logRecord(content = '创建', action = '0', model = 'welcomegiftbag', model_id = 'id')
     */
    public function createAction()
    {
        if (($this->params['c'] ?? '') == 'config') {
            return $this->outputSuccess($this->service->getConfig());
        }

        $params = $this->trimParams($this->request->getPost());
        CreateValidation::make()->validators($params);
    
        $result = $this->service->create($params);
        
        return $this->outputSuccess($result);
    }

    /**
     * @page welcomegiftbag
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'welcomegiftbag', model_id = 'bid')
     */
    public function modifyAction()
    {
        $params = $this->trimParams($this->request->getPost());
        ModifyValidation::make()->validators($params);
        $params['status'] = 0;
        $result = $this->service->modify($params);
        
        return $this->outputSuccess($result);
    }

    /**
     * @page welcomegiftbag
     * @point 置成失效
     * @logRecord(content = '置成失效', action = '1', model = 'welcomegiftbag', model_id = 'bid')
     */
    public function unvalidAction()
    {
        $params = $this->trimParams($this->request->getPost());
        UnvalidValidation::make()->validators($params);
        $params['status'] = 1;
        $result = $this->service->modify($params);
        
        return $this->outputSuccess($result);
    }

    /**
     * @page welcomegiftbag
     * @point 获取source
     */
    public function getSourceAction()
    {
        $type = $this->params['gb_type'] ?? 0;
        return $this->outputSuccess($this->service->getSource($type));
    }

}
