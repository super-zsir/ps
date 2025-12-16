<?php

namespace Imee\Controller\Operate\Push;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Push\PushConfigValidation;
use Imee\Helper\Traits\ImportTrait;
use Imee\Service\Operate\Push\PushService;

class PushconfigController extends BaseController
{
    use ImportTrait;

    /** @var PushService */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new PushService();
    }

    /**
     * @page pushconfig
     * @name push配置管理
     */
    public function mainAction()
    {
    }

    /**
     * @page pushconfig
     * @point 列表
     */
    public function listAction()
    {
        $list = $this->service->getPushList($this->params);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }

    /**
     * @page pushconfig
     * @point 创建
     * @logRecord(content = '创建', action = '0', model = 'pushconfig', model_id = 'id')
     */
    public function createAction()
    {
        PushConfigValidation::make()->validators($this->params);
        [$result, $id] = $this->service->addPush($this->params);
        if (!$result) {
            return $this->outputError(-1, $id);
        }
        return $this->outputSuccess(['id' => $id, 'after_json' => $this->params]);
    }

    /**
     * @page pushconfig
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'pushconfig', model_id = 'id')
     */
    public function modifyAction()
    {
        if (!isset($this->params['id']) || empty($this->params['id'])) {
            return $this->outputError(-1, 'id不存在');
        }
        PushConfigValidation::make()->validators($this->params);
        [$result, $msg] = $this->service->editPush($this->params);
        if (!$result) {
            return $this->outputError(-1, $msg);
        }
        return $this->outputSuccess(['after_json' => $this->params]);
    }

    /**
     * @page pushconfig
     * @point 删除
     * @logRecord(content = '删除', action = '2', model = 'pushconfig', model_id = 'id')
     */
    public function deleteAction()
    {
        $id = $this->params['id'] ?? 0;
        if (empty($this->params['id'])) {
            return $this->outputError(-1, 'id不存在');
        }
        [$result, $msg] = $this->service->deletePush($id);
        if (!$result) {
            return $this->outputError(-1, $msg);
        }
        return $this->outputSuccess(['after_json' => $this->params]);
    }

    /**
     * @page pushconfig
     * @point 审核
     * @logRecord(content = '审核', action = '3', model = 'pushconfig', model_id = 'id')
     */
    public function statusAction()
    {
        if (empty($this->params['id'])) {
            return $this->outputError(-1, 'id不存在');
        }
        if (!isset($this->params['status'])) {
            return $this->outputError(-1, 'status不存在');
        }
        [$result, $msg] = $this->service->statusPush($this->params);
        if (!$result) {
            return $this->outputError(-1, $msg);
        }
        return $this->outputSuccess(['after_json' => $this->params]);
    }
}