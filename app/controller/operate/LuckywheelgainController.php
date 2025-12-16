<?php

namespace Imee\Controller\Operate;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Luckywheel\TicketsPriceValidation;
use Imee\Models\Xs\XsLuckyWheelConfig;
use Imee\Service\Luckywheel\LuckyWheelConfigService;

class LuckywheelgainController extends BaseController
{
    /**
     * @var LuckyWheelConfigService $service
     */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new LuckyWheelConfigService('gain');
    }

    /**
     * @page luckywheelgain
     * @name 运营系统-玩法管理-LuckyWheel玩法-分成比例配置
     */
    public function mainAction()
    {
    }

    /**
     * @page luckywheelgain
     * @point 列表
     */
    public function listAction()
    {
        $res = $this->service->getList();
        return $this->outputSuccess([$res]);
    }

    /**
     * @page  luckywheelgain
     * @point 修改
     */
    public function modifyAction()
    {
        list($res, $m) = $this->service->validation($this->params);
        if (!$res) {
            return $this->outputError(-1, $m);
        }
        [$result, $msg] = $this->service->modify($this->params);
        if (!$result) {
            return $this->outputError('-1', $msg);
        }
        return $this->outputSuccess($result);
    }
}