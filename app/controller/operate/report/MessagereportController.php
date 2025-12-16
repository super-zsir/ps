<?php

namespace Imee\Controller\Operate\Report;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Report\MessageReportBannedValidation;
use Imee\Controller\Validation\Operate\Report\MessageReportRejectValidation;
use Imee\Exception\ApiException;
use Imee\Service\Operate\Report\MessageReportService;

class MessagereportController extends BaseController
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
     * @page messagereport
     * @name 消息举报
     */
    public function mainAction()
    {
    }
    
    /**
     * @page messagereport
     * @point 列表
     */
    public function listAction()
    {
        $c = $this->params['c'] ?? '';
        if ($c == 'options') {
            return $this->outputSuccess($this->service->getOptions());
        }
        $list = $this->service->getList($this->params);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }
    
    /**
     * @page messagereport
     * @point 驳回
     * @logRecord(content = '驳回', action = '1', model = 'messagereport', model_id = 'id')
     */
    public function rejectAction()
    {
        MessageReportRejectValidation::make()->validators($this->params);
        $data = $this->service->reject($this->params);
        return $this->outputSuccess($data);
    }
    
    /**
     * @page messagereport
     * @point 封禁
     * @logRecord(content = '封禁', action = '1', model = 'messagereport', model_id = 'id')
     */
    public function bannedAction()
    {
        MessageReportBannedValidation::make()->validators($this->params);
        $data = $this->service->banned($this->params);
        return $this->outputSuccess($data);
    }

    /**
     * @page messagereport
     * @point 获取封禁用户列表
     */
    public function getUserListAction()
    {
        $uid = $this->params['uid'] ?? 0;
        if (empty($uid)) {
            return $this->outputSuccess([]);
        }
        return $this->outputSuccess($this->service->getUserList($uid));
    }
}