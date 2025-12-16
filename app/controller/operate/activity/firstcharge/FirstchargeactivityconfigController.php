<?php

namespace Imee\Controller\Operate\Activity\Firstcharge;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Activity\Firstcharge\FirstChargeActivityConfigModifyValidation;
use Imee\Service\Operate\Activity\Firstcharge\FirstChargeActivityService;

class FirstchargeactivityconfigController extends BaseController
{
    /** @var FirstChargeActivityService $service */
    private $service;

    protected function onConstruct()
    {
        $this->allowSort = ['id'];
        parent::onConstruct();
        $this->service = new FirstChargeActivityService();
    }
    
    /**
     * @page firstchargeactivityconfig
     * @name 首充活动配置
     */
    public function mainAction()
    {
    }
    
    /**
     * @page firstchargeactivityconfig
     * @point 列表
     */
    public function listAction()
    {
        $c = $this->params['c'] ?? '';
        if ($c == 'options') {
            return $this->outputSuccess($this->service->getOptions());
        }
        $res = $this->service->getList();
        return $this->outputSuccess($res['data'] ?? [], ['total' => $res['total'] ?? 0]);
    }
    
    /**
     * @page firstchargeactivityconfig
     * @point 详情
     */
    public function infoAction()
    {
        return $this->outputSuccess($this->service->info($this->params['id'] ?? 0));
    }
    
    /**
     * @page firstchargeactivityconfig
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'firstchargeactivityconfig', model_id = 'id')
     */
    public function modifyAction()
    {
        FirstChargeActivityConfigModifyValidation::make()->validators($this->params);
        $data = $this->service->modify($this->params);
        return $this->outputSuccess($data);
    }
}