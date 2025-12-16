<?php

namespace Imee\Controller\Operate\Giftwall;

use Imee\Controller\BaseController;
use Imee\Service\Operate\Giftwall\GiftWallConfigService;

class GiftwallweeksearchController extends BaseController
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
     * @page giftwallweeksearch
     * @name 限时周打卡礼物查询
     */
    public function mainAction()
    {
    }
    
    /**
     * @page giftwallweeksearch
     * @point 列表
     */
    public function listAction()
    {
        $this->params['type'] = [GiftWallConfigService::TYPE_WEEK];
        $list = $this->service->getList($this->params);
        return $this->outputSuccess($list);
    }
}