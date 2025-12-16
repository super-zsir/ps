<?php

namespace Imee\Controller\Operate\Reward;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Reward\RewardSendPlatformCreateValidation;
use Imee\Controller\Validation\Operate\Reward\RewardSendPlatformModifyValidation;
use Imee\Service\Operate\Reward\RewardSendPlatformService;

class RewardsendplatformController extends BaseController
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
     * @page rewardsendplatform
     * @name 奖励模版配置
     */
    public function mainAction()
    {
    }
    
    /**
     * @page rewardsendplatform
     * @point 列表
     */
    public function listAction()
    {
        $c = $this->params['c'] ?? '';
        if ($c == 'options') {
            return $this->outputSuccess($this->_service->getOptions());
        }
        $list = $this->_service->getList($this->params);
        return $this->outputSuccess($list['data'], ['total' => $list['total']]);
    }
    
    /**
     * @page rewardsendplatform
     * @point 创建
     * @logRecord(content = '创建', action = '0', model = 'rewardsendplatform', model_id = 'id')
     */
    public function createAction()
    {
        RewardSendPlatformCreateValidation::make()->validators($this->params);
        $data = $this->_service->create($this->params);
        return $this->outputSuccess($data);
    }

    /**
     * @page rewardsendplatform
     * @point 复制
     * @logRecord(content = '复制', action = '0', model = 'rewardsendplatform', model_id = 'id')
     */
    public function copyAction()
    {
        RewardSendPlatformCreateValidation::make()->validators($this->params);
        $data = $this->_service->create($this->params);
        return $this->outputSuccess($data);
    }
    
    /**
     * @page rewardsendplatform
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'rewardsendplatform', model_id = 'id')
     */
    public function modifyAction()
    {
        RewardSendPlatformModifyValidation::make()->validators($this->params);
        $data = $this->_service->modify($this->params);
        return $this->outputSuccess($data);
    }

    /**
     * @page rewardsendplatform
     * @point 详情
     */
    public function infoAction()
    {
        return $this->outputSuccess($this->_service->info($this->params));
    }
}