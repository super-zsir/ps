<?php

namespace Imee\Controller\Operate\Reward;

use Imee\Controller\BaseController;
use Imee\Service\Operate\Reward\RewardSendService;
use Imee\Export\Operate\Reward\RewardSendUserExport;

class RewardsenduserController extends BaseController
{
    /**
     * @var RewardSendService $_service
     */
    private $_service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->_service = new RewardSendService();
    }

    /**
     * @page rewardsenduser
     * @name 查看发奖明细
     */
    public function mainAction()
    {
    }
    
    /**
     * @page rewardsenduser
     * @point 列表
     */
    public function listAction()
    {
        $list = $this->_service->getUserList($this->params);
        return $this->outputSuccess($list['data'], ['total' => $list['total']]);
    }
    
    /**
     * @page rewardsenduser
     * @point 批量重试
     */
    public function retryBatchAction()
    {
        return $this->outputSuccess($this->_service->retrySend($this->params));
    }

    /**
     * @page rewardsenduser
     * @point 导出
     */
    public function exportAction()
    {
        return $this->syncExportWork('rewardSendUserExport', RewardSendUserExport::class, $this->params);
    }
}