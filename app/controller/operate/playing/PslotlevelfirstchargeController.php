<?php

namespace Imee\Controller\Operate\Playing;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Play\Tarot\LimitLevelValidation;
use Imee\Models\Xs\XsGlobalConfig;
use Imee\Service\Operate\Play\GetKvConstant;
use Imee\Service\Operate\Play\KvBaseService;
use Imee\Service\Operate\Play\Probability\LevelAreaService;

class PslotlevelfirstchargeController extends BaseController
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
            GetKvConstant::KEY_NEW_SLOT_BIG_AREA_LIMIT_LEVEL,
            GetKvConstant::BUSINESS_TYPE_NEW_SLOT,
            GetKvConstant::INDEX_BIG_AREA_LIST,
            'pslotlevel'
        );
    }

    /**
     * @page pslotlevelfirstcharge
     * @name Greedyslot Level&First Charge
     */
    public function mainAction()
    {
    }

    /**
     * @page pslotlevelfirstcharge
     * @point 列表
     */
    public function listAction()
    {
        $c = $this->params['c'] ?? '';
        if ($c == 'switch') {
            $data = $this->levelService->getFirstChargeSwitch(XsGlobalConfig::GAME_CENTER_ID_NEW_SLOT);
            return $this->outputSuccess($data);
        }
        $res = $this->service->getLevelAndReginList();
        return $this->outputSuccess($res['data'] ?? [], ['total' => $res['total'] ?? 0]);
    }

    /**
     * @page pslotlevelfirstcharge
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'pslotlevel', model_id = 'big_area_id')
     */
    public function modifyAction()
    {
        LimitLevelValidation::make()->validators($this->params);
        $data = $this->service->setLevel($this->params);
        return $this->outputSuccess($data);
    }

    /**
     * @page pslotlevelfirstcharge
     * @point 修改首冲开关
     * @logRecord(content = '修改首冲开关', action = '1', model = 'pslotfirstcharge', model_id = 'game_center_id')
     */
    public function modifySwitchAction()
    {
        $this->params['game_center_id'] = XsGlobalConfig::GAME_CENTER_ID_NEW_SLOT;
        $this->levelService->modifySwitch($this->params);
        return $this->outputSuccess($this->params);
    }
}