<?php

namespace Imee\Controller\Operate\Topcard;

use Imee\Controller\BaseController;
use Imee\Service\Operate\Topcard\RoomTopCardSearchService;

class RoomtopcardsearchController extends BaseController
{
    /**
     * @var RoomTopCardSearchService $service
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new RoomTopCardSearchService();
    }

    /**
     * @page roomtopcardsearch
     * @name 置顶卡查询
     */
    public function mainAction()
    {
    }

    /**
     * @page roomtopcardsearch
     * @point 列表
     */
    public function listAction()
    {
        $list = $this->service->getList($this->params, $this->params['page'] ?? 1, $this->params['limit'] ?? 15);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }

    /**
     * @page roomtopcardsearch
     * @point 回收
     * @logRecord(content = '回收', action = '0', model = 'roomtopcardsearch', model_id = 'uid')
     */
    public function recoverAction()
    {
        list($res, $msg) = $this->service->recover($this->params);
        if (!$res) {
            return $this->outputError(-1, $msg);
        }
        return $this->outputSuccess($msg);
    }
}