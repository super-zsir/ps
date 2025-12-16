<?php

namespace Imee\Controller\Operate\Playing;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Play\Tarot\LimitLevelValidation;
use Imee\Models\Xs\XsGlobalConfig;
use Imee\Service\Operate\Play\GetKvConstant;
use Imee\Service\Operate\Play\KvBaseService;
use Imee\Service\Operate\Play\Probability\LevelAreaService;

class GreedyboxlevelfirstchargeController extends BaseController
{
    /**
     * @var KvBaseService
     */
    private $service;

    /**
     * @var LevelAreaService $levelService
     */
    private $levelService;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->levelService = new LevelAreaService();
        $this->service = new KvBaseService(
            GetKvConstant::KEY_GREEDY_BOX_BIG_AREA_LIMIT_LEVEL,
            GetKvConstant::BUSINESS_TYPE_GREEDY_BOX,
            GetKvConstant::INDEX_BIG_AREA_LIST,
            'greedyboxlevel'
        );
    }

    /**
     * @page greedyboxlevelfirstcharge
     * @name Greedy Box Level&First Charge
     */
    public function mainAction()
    {
    }

    /**
     * @page greedyboxlevelfirstcharge
     * @point 列表
     */
    public function listAction()
    {
        $c = $this->params['c'] ?? '';
        if ($c == 'switch') {
            $data = $this->levelService->getFirstChargeSwitch(XsGlobalConfig::GAME_CENTER_ID_GREEDY_BOX);
            return $this->outputSuccess($data);
        }
        $res = $this->service->getLevelAndReginList();
        return $this->outputSuccess($res['data'] ?? [], ['total' => $res['total'] ?? 0]);
    }

    /**
     * @page greedyboxlevelfirstcharge
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'greedyboxlevel', model_id = 'big_area_id')
     */
    public function modifyAction()
    {
        LimitLevelValidation::make()->validators($this->params);
        $data = $this->service->setLevel($this->params);
        return $this->outputSuccess($data);
    }

    /**
     * @page greedyboxlevelfirstcharge
     * @point 修改首冲开关
     * @logRecord(content = '修改首冲开关', action = '1', model = 'greedyboxfirstcharge', model_id = 'game_center_id')
     */
    public function modifySwitchAction()
    {
        $this->params['game_center_id'] = XsGlobalConfig::GAME_CENTER_ID_GREEDY_BOX;
        $this->levelService->modifySwitch($this->params);
        return $this->outputSuccess($this->params);
    }
}