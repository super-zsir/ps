<?php

namespace Imee\Controller\Operate;

use Imee\Controller\BaseController;
use Imee\Service\Operate\Push\PushPlanService;

class PushlogController extends BaseController
{
    /**
     * @var PushPlanService $service
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new PushPlanService();
    }

    /**
     * @page pushlog
     * @name 消息通知管理-推送记录
     */
    public function mainAction()
    {
    }

    /**
     * @page pushlog
     * @point 列表
     */
    public function listAction()
    {
        [$res, $msg, $data] = $this->service->getLogList($this->params);
        if (!$res) {
            return $this->outputError(-1, $msg);
        }
        return $this->outputSuccess($data['list'], ['total' => $data['total']]);
    }
}