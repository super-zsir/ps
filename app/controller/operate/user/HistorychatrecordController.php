<?php

namespace Imee\Controller\Operate\User;

use Imee\Controller\BaseController;
use Imee\Service\Operate\User\UserListService;

class HistorychatrecordController extends BaseController
{
    /**
     * @var UserListService $service
     */
    private $service;
    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new UserListService();
    }
    
    /**
     * @page historychatrecord
     * @name 历史聊天记录
     */
    public function mainAction()
    {
    }
    
    /**
     * @page historychatrecord
     * @point 列表
     */
    public function listAction()
    {
        $list = $this->service->getOrderChatLog($this->params);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }
}