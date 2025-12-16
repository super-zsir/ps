<?php

namespace Imee\Controller\Operate;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Game\GameWebValidation;
use Imee\Service\Operate\Game\GameWebService;

class GamesystemwebController extends BaseController
{
    /**
     * @var GameWebService $service
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new GameWebService();
    }

    /**
     * @page gamesystemweb
     * @name 游戏配置
     */
    public function mainAction()
    {
    }

    /**
     * @page gamesystemweb
     * @point 列表
     */
    public function listAction()
    {
        $list = $this->service->getList($this->params);
        return $this->outputSuccess($list);
    }

    /**
     * @page gamesystemweb
     * @point 修改状态
     * @logRecord(content = '修改状态', action = '0', model = 'gamesystemweb', model_id = 'poolID')
     */
    public function stateAction()
    {
        $data = $this->service->modify($this->params);
        return $this->outputSuccess($data);
    }

    /**
     * @page gamesystemweb
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'gamesystemweb', model_id = 'poolID')
     */
    public function modifyAction()
    {
        GameWebValidation::make()->validators($this->params);
        $data = $this->service->modify($this->params);
        return $this->outputSuccess($data);
    }
}