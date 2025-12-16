<?php

namespace Imee\Controller\Operate\Giftwall;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Giftwall\GiftWallConfigCreateValidation;
use Imee\Controller\Validation\Operate\Giftwall\GiftWallConfigModifyValidation;
use Imee\Service\Operate\Giftwall\GiftWallConfigService;

class GiftwallconfigController extends BaseController
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
     * @page giftwallconfig
     * @name 礼物手动设置
     */
    public function mainAction()
    {
    }
    
    /**
     * @page giftwallconfig
     * @point 列表
     */
    public function listAction()
    {
        $types = [GiftWallConfigService::TYPE_MANUAL, GiftWallConfigService::TYPE_RESERVA];
        if (empty($this->params['type'])) {
            $this->params['type'] = $types;
        } elseif (!in_array($this->params['type'], $types)) {
            $this->params['type'] = $types;
        } else {
            $this->params['type'] = [(int)$this->params['type']];
        }

        $list = $this->service->getList($this->params);
        return $this->outputSuccess($list);
    }

    /**
     * @page giftwallconfig
     * @point 创建
     * @logRecord(content = '创建', action = '0', model = 'giftwallconfig', model_id = 'config_id')
     */
    public function createAction()
    {
        $c = $this->params['c'] ?? '';
        if ($c == 'gift_type') {
            return $this->outputSuccess(['gift_type' => $this->params['type']]);
        }
        GiftWallConfigCreateValidation::make()->validators($this->params);
        return $this->outputSuccess($this->service->setConfig($this->params));
    }

    /**
     * @page giftwallconfig
     * @point 获取奖励ID
     */
    public function rewardAction()
    {
        $enum = $this->service->getAwardList($this->params['award_type']);
        return $this->outputSuccess($enum);
    }
    
    /**
     * @page giftwallconfig
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'giftwallconfig', model_id = 'config_id')
     */
    public function modifyAction()
    {
        GiftWallConfigModifyValidation::make()->validators($this->params);
        return $this->outputSuccess($this->service->setConfig($this->params, true));
    }
}