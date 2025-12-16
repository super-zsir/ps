<?php

namespace Imee\Controller\Operate;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\WebOfflinePackageAddValidation;
use Imee\Controller\Validation\Operate\WebOfflinePackageEditValidation;
use Imee\Service\Operate\WebOfflinePackageConfigService;

class WebofflinepackageconfigController extends BaseController
{
    /**
     * @var WebOfflinePackageConfigService $service
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new WebOfflinePackageConfigService();
    }
    
    /**
     * @page webofflinepackageconfig
     * @name 网页离线包配置管理
     */
    public function mainAction()
    {
    }
    
    /**
     * @page webofflinepackageconfig
     * @point 列表
     */
    public function listAction()
    {
        $list = $this->service->getList($this->params);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }
    
    /**
     * @page webofflinepackageconfig
     * @point 创建
     * @logRecord(content = '创建', action = '0', model = 'webofflinepackageconfig', model_id = 'id')
     */
    public function createAction()
    {
        WebOfflinePackageAddValidation::make()->validators($this->params);
        $data = $this->service->create($this->params);
        return $this->outputSuccess($data);
    }
    
    /**
     * @page webofflinepackageconfig
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'webofflinepackageconfig', model_id = 'id')
     */
    public function modifyAction()
    {
        WebOfflinePackageEditValidation::make()->validators($this->params);
        $data = $this->service->modify($this->params);
        return $this->outputSuccess($data);
    }
}