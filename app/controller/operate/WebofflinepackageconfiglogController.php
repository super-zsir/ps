<?php

namespace Imee\Controller\Operate;

use Imee\Controller\BaseController;
use Imee\Service\Operate\WebOfflinePackageConfigService;

class WebofflinepackageconfiglogController extends BaseController
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
     * @page webofflinepackageconfiglog
     * @name 操作日志
     */
    public function mainAction()
    {
    }
    
    /**
     * @page webofflinepackageconfiglog
     * @point 列表
     */
    public function listAction()
    {
        $list = $this->service->getLogList($this->params);
        return $this->outputSuccess($list);
    }
}