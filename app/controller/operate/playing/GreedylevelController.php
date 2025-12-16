<?php

namespace Imee\Controller\Operate\Playing;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Play\Probability\LevelAreaValidation;
use Imee\Models\Xs\XsGlobalConfig;
use Imee\Service\Operate\Play\Probability\LevelAreaService;

class GreedylevelController extends BaseController
{
    /**
     * @var LevelAreaService
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new LevelAreaService();
    }

    /**
     * @page greedylevel
     * @name Greedy Level
     */
    public function mainAction()
    {
    }

    /**
     * @page greedylevel
     * @point 列表
     */
    public function listAction()
    {
        $res = $this->service->getList(XsGlobalConfig::GAME_CENTER_ID_GREEDY);
        return $this->outputSuccess($res['data'] ?? [], ['total' => $res['total'] ?? 0]);
    }

    /**
     * @page greedylevel
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'greedylevel', model_id = 'bigarea_id')
     */
    public function modifyAction()
    {
        LevelAreaValidation::make()->validators($this->params);
        $this->params['game_id'] = XsGlobalConfig::GAME_CENTER_ID_GREEDY;
        $this->service->edit($this->params);
        return $this->outputSuccess($this->params);
    }
}