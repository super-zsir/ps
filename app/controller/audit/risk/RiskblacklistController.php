<?php

namespace Imee\Controller\Audit\Risk;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Audit\RiskBlacklist\AddValidation;
use Imee\Service\Risk\RiskBlacklistService;

class RiskblacklistController extends BaseController
{
    /** @var  RiskBlacklistService $service */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new RiskBlacklistService();
        $this->params['admin_id'] = $this->params['admin_uid'];
    }

    /**
     * @page riskblacklist
     * @name 审核系统-风控管理-风控黑名单
     */
    public function mainAction()
    {
    }

    /**
     * @page  riskblacklist
     * @point 列表
     */
    public function listAction()
    {
        $data = $this->service->getListAndTotal(
            $this->params, 'id desc', $this->params['page'] ?? 1, $this->params['limit'] ?? 15
        );

        return $this->outputSuccess($data['data'], ['total' => $data['total']]);
    }

    /**
     * @page riskblacklist
     * @point 新增
     */
    public function addAction()
    {
        AddValidation::make()->validators($this->params);

        [$result, $id] = $this->service->add($this->params);
        if (!$result) {
            return $this->outputError('-1', $id);
        }
        return $this->outputSuccess($id);
    }

    /**
     * @page riskblacklist
     * @point 修改
     */
    public function editAction()
    {
        if (empty($this->params['id'])) {
            return $this->outputError('-1', 'id必须');
        }
        AddValidation::make()->validators($this->params);

        [$result, $id] = $this->service->edit($this->params['id'], $this->params);
        if (!$result) {
            return $this->outputError('-1', $id);
        }
        return $this->outputSuccess($id);
    }

    /**
     * @page riskblacklist
     * @point 状态
     */
    public function statusAction()
    {
        if (empty($this->params['id'])) {
            return $this->outputError('-1', 'id必须');
        }
        if (!isset($this->params['status'])) {
            return $this->outputError('-1', 'status必须');
        }

        [$result, $msg] = $this->service->status($this->params['id'], $this->params['status']);
        if (!$result) {
            return $this->outputError('-1', $msg);
        }
        return $this->outputSuccess($result);
    }
}
