<?php

namespace Imee\Controller\Operate\Playing;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Luckygift\LimitConfigValidation;
use Imee\Models\Xs\XsLuckyFruitsLimitConfig;
use Imee\Service\Operate\Play\Luckyfruit\LuckyFruitsLimitConfigService;

class LuckyfruitslimitconfigbulkController extends BaseController
{
    /**
     * @var LuckyFruitsLimitConfigService $service
     */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new LuckyFruitsLimitConfigService();
    }

    /**
     * @page luckyfruitslimitconfigbulk
     * @name Rewards Limit Config
     */
    public function mainAction()
    {

    }

    /**
     * @page luckyfruitslimitconfigbulk
     * @point list
     */
    public function listAction()
    {
        $data = $this->service->getListAndTotal($this->params, XsLuckyFruitsLimitConfig::CONFIG_TYPE_BULK);
        return $this->outputSuccess($data['data'], ['total' => $data['total']]);
    }

    /**
     * @page  luckyfruitslimitconfigbulk
     * @point 创建
     * @logRecord(content = "创建", action = "0", model = "XsLuckyFruitsLimitConfig", model_id = "id")
     */
    public function createAction()
    {
        LimitConfigValidation::make()->validators($this->params);
        $data = $this->service->add($this->params, XsLuckyFruitsLimitConfig::CONFIG_TYPE_BULK);
        return $this->outputSuccess($data);
    }

    /**
     * @page  luckyfruitslimitconfigbulk
     * @point 修改
     * @logRecord(content = "修改", action = "1", model = "XsLuckyFruitsLimitConfig", model_id = "id")
     */
    public function modifyAction()
    {
        LimitConfigValidation::make()->validators($this->params);
        $data = $this->service->modify($this->params, XsLuckyFruitsLimitConfig::CONFIG_TYPE_BULK);
        return $this->outputSuccess($data);
    }

    /**
     * @page  luckyfruitslimitconfigbulk
     * @point 删除
     * @logRecord(content = "删除", action = "2", model = "XsLuckyFruitsLimitConfig", model_id = "id")
     */
    public function deleteAction()
    {
        $data = $this->service->delete($this->params, XsLuckyFruitsLimitConfig::CONFIG_TYPE_BULK);
        return $this->outputSuccess($data);
    }

    /**
     * @page  luckyfruitslimitconfigbulk
     * @point 批量删除
     * @logRecord(content = "批量删除", action = "2", model = "XsLuckyFruitsLimitConfig", model_id = "id")
     */
    public function deleteBatchAction()
    {
        $data = $this->service->deleteBatch($this->params, XsLuckyFruitsLimitConfig::CONFIG_TYPE_BULK);
        return $this->outputSuccess($data);
    }
}