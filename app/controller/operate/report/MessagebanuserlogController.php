<?php

namespace Imee\Controller\Operate\Report;

use Imee\Controller\BaseController;
use Imee\Service\Operate\Report\MessageReportService;

class MessagebanuserlogController extends BaseController
{
    /**
     * @var MessageReportService $service
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new MessageReportService();
    }
    
    /**
     * @page messagebanuserlog
     * @name 封禁记录
     */
    public function mainAction()
    {
    }
    
    /**
     * @page messagebanuserlog
     * @point 列表
     */
    public function listAction()
    {
        $list = $this->service->getLogList($this->params);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }
}