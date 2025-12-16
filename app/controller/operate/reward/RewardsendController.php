<?php

namespace Imee\Controller\Operate\Reward;

use Imee\Comp\Common\Fixed\Csv;
use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Reward\RewardSendCreateValidation;
use Imee\Service\Helper;
use Imee\Service\Operate\Reward\RewardSendService;

class RewardsendController extends BaseController
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
     * @page rewardsend
     * @name 奖励发放
     */
    public function mainAction()
    {
    }

    /**
     * @page rewardsend
     * @point 列表
     */
    public function listAction()
    {
        $c = $this->params['c'] ?? '';
        if ($c == 'tpl') {
            (new Csv())->exportToCsv(['UID', '上传时需删除表头'], [], 'reward_send_uid');
            exit;
        }
        $list = $this->_service->getList($this->params);
        return $this->outputSuccess($list['data'], ['total' => $list['total']]);
    }

    /**
     * @page rewardsend
     * @point 创建
     * @logRecord(content = '创建', action = '0', model = 'rewardsend', model_id = 'id')
     */
    public function createAction()
    {
        RewardSendCreateValidation::make()->validators($this->params);
        $data = $this->_service->create($this->params);
        return $this->outputSuccess($data);
    }

    /**
     * @page rewardsend
     * @point 批量创建
     * @logRecord(content = '批量创建', action = '0', model = 'rewardsend', model_id = 'id')
     */
    public function createBatchAction()
    {
        $this->params['uid_list'] = $this->_service->importUid();
        RewardSendCreateValidation::make()->validators($this->params);
        $data = $this->_service->create($this->params);
        return $this->outputSuccess($data);
    }

    /**
     * @page rewardsend
     * @point 批量审核
     * @logRecord(content = '批量审核', action = '1', model = 'rewardsend', model_id = 'id')
     */
    public function auditBatchAction()
    {
        $data = $this->_service->auditBatch($this->params, true);
        return $this->outputSuccess($data);
    }

    /**
     * @page rewardsend
     * @point 审核
     * @logRecord(content = '审核', action = '1', model = 'rewardsend', model_id = 'id')
     */
    public function auditAction()
    {
        $data = $this->_service->auditBatch($this->params);
        return $this->outputSuccess($data);
    }
}