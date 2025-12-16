<?php

namespace Imee\Controller\Operate\User;

use Imee\Controller\BaseController;
use Imee\Service\Operate\User\GameplayBlacklistService;

class GameplayblacklistlogController extends BaseController
{
    /**
     * @var GameplayBlacklistService $service
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new GameplayBlacklistService();
    }
    
    /**
     * @page gameplayblacklistlog
     * @name 玩法黑名单日志
     */
    public function mainAction()
    {
    }
    
    /**
     * @page gameplayblacklistlog
     * @point 列表
     */
    public function listAction()
    {
        $list = $this->service->getLogList($this->params);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }
}