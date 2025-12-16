<?php

namespace Imee\Controller\Operate\Reward;

use Imee\Controller\BaseController;
use Imee\Models\Xsst\XsstRewardWhitelist;
use Imee\Service\Operate\Reward\RewardSendPlatformService;

class RewardauditsendwhitelistController extends BaseController
{
    /**
     * @var RewardSendPlatformService $_service
     */
    private $_service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->_service = new RewardSendPlatformService();
        $this->params['type'] = XsstRewardWhitelist::TYPE_REWARD_SEND_AUDIT;
    }
    
    /**
     * @page rewardauditsendwhitelist
     * @name 审核白名单
     */
    public function mainAction()
    {
    }
    
    /**
     * @page rewardauditsendwhitelist
     * @point 列表
     */
    public function listAction()
    {
        $list = $this->_service->getWhitelistList($this->params);
        return $this->outputSuccess($list['data'], ['total' => $list['data']]);
    }
    
    /**
     * @page rewardauditsendwhitelist
     * @point 创建
     * @logRecord(content = '创建', action = '0', model = 'rewardauditsendwhitelist', model_id = 'id')
     */
    public function createAction()
    {
        return $this->outputSuccess($this->_service->whitelistCreate($this->params));
    }
    
    /**
     * @page rewardauditsendwhitelist
     * @point 删除
     * @logRecord(content = '删除', action = '2', model = 'rewardauditsendwhitelist', model_id = 'id')
     */
    public function deleteAction()
    {
        return $this->outputSuccess($this->_service->whitelistDelete($this->params));
    }
}