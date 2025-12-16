<?php

namespace Imee\Controller\Operate\Playing;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Play\Tarot\LimitLevelValidation;
use Imee\Models\Xs\XsGlobalConfig;
use Imee\Service\Operate\Play\GetKvConstant;
use Imee\Service\Operate\Play\KvBaseService;
use Imee\Service\Operate\Play\Probability\LevelAreaService;

class GreedybrutallevelfirstchargeController extends BaseController
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
            GetKvConstant::KEY_SWEET_CANDY_BIG_AREA_LIMIT_LEVEL,
            GetKvConstant::BUSINESS_TYPE_SWEET_CANDY,
            GetKvConstant::INDEX_BIG_AREA_LIST,
            'greedybrutallevel'
        );
    }

    /**
     * @page greedybrutallevelfirstcharge
     * @name Greedy Brute Level&First Charge
     */
    public function mainAction()
    {
    }

    /**
     * @page greedybrutallevelfirstcharge
     * @point 列表
     */
    public function listAction()
    {
        $c = $this->params['c'] ?? '';
        if ($c == 'switch') {
            $data = $this->levelService->getFirstChargeSwitch(XsGlobalConfig::GAME_CENTER_ID_GREEDY_BRUTAL);
            return $this->outputSuccess($data);
        }
        $res = $this->service->getLevelAndReginList();
        return $this->outputSuccess($res['data'] ?? [], ['total' => $res['total'] ?? 0]);
    }

    /**
     * @page greedybrutallevelfirstcharge
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'greedybrutallevel', model_id = 'big_area_id')
     */
    public function modifyAction()
    {
        LimitLevelValidation::make()->validators($this->params);
        $data = $this->service->setLevel($this->params);
        return $this->outputSuccess($data);
    }


    /**
     * @page greedybrutallevelfirstcharge
     * @point 修改首冲开关
     * @logRecord(content = '修改首冲开关', action = '1', model = 'greedybrutalfirstcharge', model_id = 'game_center_id')
     */
    public function modifySwitchAction()
    {
        $this->params['game_center_id'] = XsGlobalConfig::GAME_CENTER_ID_GREEDY_BRUTAL;
        $this->levelService->modifySwitch($this->params);
        return $this->outputSuccess($this->params);
    }
}