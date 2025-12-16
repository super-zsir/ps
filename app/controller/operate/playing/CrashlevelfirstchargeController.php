<?php

namespace Imee\Controller\Operate\Playing;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Play\Probability\LevelAreaValidation;
use Imee\Models\Xs\XsGlobalConfig;
use Imee\Service\Operate\Play\Probability\LevelAreaService;

class CrashlevelfirstchargeController extends BaseController
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
     * @page crashlevelfirstcharge
     * @name Crash Level&First Charge
     */
    public function mainAction()
    {
    }

    /**
     * @page crashlevelfirstcharge
     * @point 列表
     */
    public function listAction()
    {
        $c = $this->params['c'] ?? '';
        if ($c == 'switch') {
            $data = $this->service->getFirstChargeSwitch(XsGlobalConfig::GAME_CENTER_ID_ROCKET_CRASH);
            return $this->outputSuccess($data);
        }
        $res = $this->service->getList(XsGlobalConfig::GAME_CENTER_ID_ROCKET_CRASH);
        return $this->outputSuccess($res['data'] ?? [], ['total' => $res['total'] ?? 0]);
    }

    /**
     * @page crashlevelfirstcharge
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'crashlevel', model_id = 'bigarea_id')
     */
    public function modifyAction()
    {
        LevelAreaValidation::make()->validators($this->params);
        $this->params['game_id'] = XsGlobalConfig::GAME_CENTER_ID_ROCKET_CRASH;
        $this->service->edit($this->params);
        return $this->outputSuccess($this->params);
    }

    /**
     * @page crashlevelfirstcharge
     * @point 修改首冲开关
     * @logRecord(content = '修改首冲开关', action = '1', model = 'crashfirstcharge', model_id = 'game_center_id')
     */
    public function modifySwitchAction()
    {
        $this->params['game_center_id'] = XsGlobalConfig::GAME_CENTER_ID_ROCKET_CRASH;
        $this->service->modifySwitch($this->params);
        return $this->outputSuccess($this->params);
    }
}