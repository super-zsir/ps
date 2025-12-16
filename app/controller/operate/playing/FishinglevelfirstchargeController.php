<?php

namespace Imee\Controller\Operate\Playing;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Play\Tarot\LimitLevelValidation;
use Imee\Models\Xs\XsGlobalConfig;
use Imee\Service\Operate\Play\GetKvConstant;
use Imee\Service\Operate\Play\KvBaseService;
use Imee\Service\Operate\Play\Probability\LevelAreaService;

class FishinglevelfirstchargeController extends BaseController
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
            GetKvConstant::KEY_FISHING_BIG_AREA_LIMIT_LEVEL,
            GetKvConstant::BUSINESS_TYPE_FISHING,
            GetKvConstant::INDEX_BIG_AREA_LIST,
            'fishinglevel'
        );
    }

    /**
     * @page fishinglevelfirstcharge
     * @name Fishing Level&First Charge
     */
    public function mainAction()
    {
    }

    /**
     * @page fishinglevelfirstcharge
     * @point 列表
     */
    public function listAction()
    {
        $c = $this->params['c'] ?? '';
        if ($c == 'switch') {
            $data = $this->levelService->getFirstChargeSwitch(XsGlobalConfig::GAME_CENTER_ID_FISHING);
            return $this->outputSuccess($data);
        }
        $res = $this->service->getLevelAndReginList();
        return $this->outputSuccess($res['data'] ?? [], ['total' => $res['total'] ?? 0]);
    }

    /**
     * @page fishinglevelfirstcharge
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'fishinglevel', model_id = 'big_area_id')
     */
    public function modifyAction()
    {
        LimitLevelValidation::make()->validators($this->params);
        $data = $this->service->setLevel($this->params);
        return $this->outputSuccess($data);
    }


    /**
     * @page fishinglevelfirstcharge
     * @point 修改首冲开关
     * @logRecord(content = '修改首冲开关', action = '1', model = 'fishingfirstcharge', model_id = 'game_center_id')
     */
    public function modifySwitchAction()
    {
        $this->params['game_center_id'] = XsGlobalConfig::GAME_CENTER_ID_FISHING;
        $this->levelService->modifySwitch($this->params);
        return $this->outputSuccess($this->params);
    }
}