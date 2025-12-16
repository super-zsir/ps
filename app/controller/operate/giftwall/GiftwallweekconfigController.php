<?php

namespace Imee\Controller\Operate\Giftwall;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Giftwall\GiftWallConfigWeekModifyValidation;
use Imee\Service\Operate\Giftwall\GiftWallConfigService;

class GiftwallweekconfigController extends BaseController
{
    /**
     * @var GiftWallConfigService $service
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new GiftWallConfigService();
    }
    
    /**
     * @page giftwallweekconfig
     * @name 限时礼物设置自动
     */
    public function mainAction()
    {
    }
    
    /**
     * @page giftwallweekconfig
     * @point 列表
     */
    public function listAction()
    {
        $c = $this->params['c'] ?? '';
        if ($c == 'check') {
            return $this->outputSuccess([
                'is_confirm' => 1,
                'confirm_text' => '该修改下周一开始生效，确定修改吗？'
            ]);
        }
        $list = $this->service->getWeekList($this->params);
        return $this->outputSuccess($list);
    }
    
    /**
     * @page giftwallweekconfig
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'giftwallweekconfig', model_id = 'pool_num')
     */
    public function modifyAction()
    {
        GiftWallConfigWeekModifyValidation::make()->validators($this->params);
        return $this->outputSuccess($this->service->setWeekConfig($this->params));
    }
}