<?php

namespace Imee\Controller\Operate;

use Imee\Controller\BaseController;
use Imee\Service\Operate\VideoConfigFileManageService;

class VideoconfigfilemanagelogController extends BaseController
{
    /**
     * @var VideoConfigFileManageService $service
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new VideoConfigFileManageService();
    }
    
    /**
     * @page videoconfigfilemanagelog
     * @name 操作日志
     */
    public function mainAction()
    {
    }
    
    /**
     * @page videoconfigfilemanagelog
     * @point 列表
     */
    public function listAction()
    {
        $res = $this->service->getLogList($this->params);
        return $this->outputSuccess($res);
    }
}