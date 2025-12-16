<?php

namespace Imee\Controller\Operate;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Push\PushPlanValidation;
use Imee\Service\Operate\Push\PushPlanService;

class PushplanController extends BaseController
{
    /**
     * @var PushPlanService $service
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new PushPlanService();
    }

    /**
     * @page pushplan
     * @name 运营系统-消息通知管理-Push推送计划
     */
    public function mainAction()
    {
    }

    /**
     * @page pushplan
     * @point 列表
     */
    public function listAction()
    {
        [$res, $msg, $data] = $this->service->getList($this->params);
        if (!$res) {
            return $this->outputError(-1, $msg);
        }
        return $this->outputSuccess($data['list'], ['total' => $data['total']]);
    }

    /**
     * @page pushplan
     * @point 创建
     * @logRecord(content = '创建', action = '0', model = 'pushplan', model_id = 'id')
     */
    public function createAction()
    {
        $c = $this->params['c'] ?? '';
        if ($c == 'options') {
            $options = $this->service->options();
            return $this->outputSuccess($options);
        } else if ($c == 'upload') {
            [$res, $msg, $data] = $this->service->uploadUids();
            if (!$res) {
                return $this->outputError('-1', $msg);
            }
            return $this->outputSuccess($data);
        }
        PushPlanValidation::make()->validators($this->params);
        [$res, $msg, $data] = $this->service->add($this->params);
        if (!$res) {
            return $this->outputError(-1, $msg);
        }
        return $this->outputSuccess($data);
    }

    /**
     * @page pushplan
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'pushplan', model_id = 'id')
     */
    public function modifyAction()
    {
        $id = $this->params['id'] ?? 0;
        if (empty($id)) {
            return $this->outputError(-1, 'ID必传');
        }
        PushPlanValidation::make()->validators($this->params);
        [$res, $msg] = $this->service->edit($this->params);
        if (!$res) {
            return $this->outputError(-1, $msg);
        }
        return $this->outputSuccess($msg);
    }

    /**
     * @page pushplan
     * @point 删除
     * @logRecord(content = '删除', action = '2', model = 'pushplan', model_id = 'id')
     */
    public function deleteAction()
    {
        $id = $this->params['id'] ?? 0;
        if (empty($id)) {
            return $this->outputError(-1, 'ID必传');
        }
        [$res, $msg] = $this->service->delete($id);
        if (!$res) {
            return $this->outputError(-1, $msg);
        }
        return $this->outputSuccess($msg);
    }

    /**
     * @page pushplan
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
     * @page pushplan
     * @point 复制
     */
    public function copyAction()
    {
        $id = $this->params['id'] ?? 0;
        if (empty($id)) {
            return $this->outputError(-1, 'ID必传');
        }
        PushPlanValidation::make()->validators($this->params);
        [$res, $msg, $data] = $this->service->copy($this->params);
        if (!$res) {
            return $this->outputError(-1, $msg);
        }
        return $this->outputSuccess($data);
    }

    /**
     * @page pushplan
     * @point 停止
     */
    public function stopAction()
    {
        $id = $this->params['id'] ?? 0;
        if (empty($id)) {
            return $this->outputError(-1, 'ID必传');
        }
        [$res, $msg, $data] = $this->service->stop($id);
        if (!$res) {
            return $this->outputError(-1, $msg);
        }
        return $this->outputSuccess($data);
    }
}