<?php

namespace Imee\Controller\Operate\Reward;

use Imee\Controller\BaseController;
use Imee\Service\Operate\Reward\RewardSendPlatformService;

class RewardsendplatforminfoController extends BaseController
{
    /**
     * @var RewardSendPlatformService $_service
     */
    private $_service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->_service = new RewardSendPlatformService();
    }
    
    /**
     * @page rewardsendplatforminfo
     * @name 奖励模版配置详情
     */
    public function mainAction()
    {
    }
    
    /**
     * @page rewardsendplatforminfo
     * @point 列表
     */
    public function listAction()
    {
        $list = $this->_service->getRewardList($this->params);
        return $this->outputSuccess($list);
    }
    
    /**
     * @page rewardsendplatforminfo
     * @point 创建
     * @logRecord(content = '创建', action = '0', model = 'rewardsendplatforminfo', model_id = 'index')
     */
    public function createAction()
    {
    }
    
    /**
     * @page rewardsendplatforminfo
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'rewardsendplatforminfo', model_id = 'index')
     */
    public function modifyAction()
    {
    }
    
    /**
     * @page rewardsendplatforminfo
     * @point 删除
     * @logRecord(content = '删除', action = '2', model = 'rewardsendplatforminfo', model_id = 'index')
     */
    public function deleteAction()
    {
    }
    
    /**
     * @page rewardsendplatforminfo
     * @point 导出
     */
    public function exportAction()
    {
    }
}