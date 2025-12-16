<?php

namespace Imee\Controller\Finance;

use Imee\Controller\BaseController;
use Imee\Service\Finance\CurrencyRateService;

class CurrencyrateController extends BaseController
{

    /**
     * @var CurrencyRateService
     */
    private $service;

    public function onConstruct()
    {
        $this->service = new CurrencyRateService();
        parent::onConstruct();
    }
    
    /**
     * @page currencyrate
     * @name 汇率看板
     */
    public function mainAction()
    {
    }
    
    /**
     * @page currencyrate
     * @point 列表
     */
    public function listAction()
    {
        $result = $this->service->getData($this->params);
        return $this->outputSuccess($result['data'], ['total' => $result['total']]);
    }

    /**
     * @page currencyrate
     * @point 汇率计算
     */
    public function calculateRateAction()
    {
        if (array_get($this->params, 'flg') == 'check') {
            list($confirmFlg, $confirmData) = $this->service->calculateRate($this->params);
            if ($confirmFlg) {
                return $this->outputSuccess($confirmData);
            } else {
                return $this->outputSuccess(['is_confirm' => 0, 'confirm_text' => '']);
            }
        }
        return $this->outputSuccess(['is_confirm' => 0, 'confirm_text' => '']);
    }
}