<?php

namespace Imee\Controller\Operate\Playing;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Play\Crash\LimitConfigAddValidation;
use Imee\Controller\Validation\Operate\Play\Crash\LimitConfigEditValidation;
use Imee\Models\Xs\XsRocketCrashLimitConfig;
use Imee\Service\Operate\Play\Crash\LimitConfigService;

class CrashtotalController extends BaseController
{
    /** @var LimitConfigService $service */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new LimitConfigService();
        $this->params['config_type'] = XsRocketCrashLimitConfig::CRASH_TOTAL;
    }
    
    /**
     * @page crashtotal
     * @name Crash Total
     */
    public function mainAction()
    {
    }
    
    /**
     * @page crashtotal
     * @point 列表
     */
    public function listAction()
    {
        $res = $this->service->getList($this->params);
        return $this->outputSuccess($res['data'] ?? [], ['total' => $res['total'] ?? 0]);
    }
    
    /**
     * @page crashtotal
     * @point 创建
     * @logRecord(content = '创建', action = '0', model = 'crashtotal', model_id = 'id')
     */
    public function createAction()
    {
        LimitConfigAddValidation::make()->validators($this->params);
        return $this->outputSuccess($this->service->create($this->params));
    }
    
    /**
     * @page crashtotal
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'crashtotal', model_id = 'id')
     */
    public function modifyAction()
    {
        LimitConfigEditValidation::make()->validators($this->params);
        return $this->outputSuccess($this->service->modify($this->params));
    }
}