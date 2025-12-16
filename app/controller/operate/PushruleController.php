<?php

namespace Imee\Controller\Operate;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Push\PushRuleValidation;
use Imee\Service\Operate\Push\PushRuleService;

class PushruleController extends BaseController
{
    /**
     * @var PushRuleService $service
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new PushRuleService();
    }

    /**
     * @page pushrule
     * @name 消息通知管理-Push规则配置
     */
    public function mainAction()
    {
    }

    /**
     * @page pushrule
     * @point 列表
     */
    public function listAction()
    {
        [$res, $msg, $data] = $this->service->getList($this->params);
        if (!$res) {
            return $this->outputError(-1, $msg);
        }
        return $this->outputSuccess($data['list'] ?? [], ['total' => $data['total'] ?? 0]);
    }

    /**
     * @page pushrule
     * @point 创建
     * @logRecord(content = '创建', action = '0', model = 'pushrule', model_id = 'id')
     */
    public function createAction()
    {
        PushRuleValidation::make()->validators($this->params);
        [$res, $msg, $data] = $this->service->add($this->params);
        if (!$res) {
            return $this->outputError(-1, $msg);
        }
        return $this->outputSuccess($data);
    }

    /**
     * @page pushrule
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'pushrule', model_id = 'id')
     */
    public function modifyAction()
    {
        $id = $this->params['id'] ?? 0;
        if (empty($id)) {
            return $this->outputError(-1, 'ID必传');
        }
        PushRuleValidation::make()->validators($this->params);
        [$res, $msg, $data] = $this->service->edit($this->params);
        if (!$res) {
            return $this->outputError(-1, $msg);
        }
        return $this->outputSuccess($data);
    }

    /**
     * @page pushrule
     * @point 详情
     */
    public function infoAction()
    {
        $id = $this->params['id'] ?? 0;
        if (empty($id)) {
            return $this->outputError(-1, 'ID必传');
        }
        [$res, $msg, $data] = $this->service->info($id);
        if (!$res) {
            return $this->outputError(-1, $msg);
        }
        return $this->outputSuccess($data);
    }

    /**
     * @page pushrule
     * @point 修改状态
     * @logRecord(content = '修改状态', action = '1', model = 'pushrule', model_id = 'id')
     */
    public function statusAction()
    {
        $id = $this->params['id'] ?? 0;
        $status = $this->params['status'] ?? 0;
        if (empty($id) || empty($status)) {
            return $this->outputError(-1, 'ID/状态必传');
        }
        [$res, $msg, $data] = $this->service->status($id, $status);
        if (!$res) {
            return $this->outputError(-1, $msg);
        }
        return $this->outputSuccess($data);
    }
}