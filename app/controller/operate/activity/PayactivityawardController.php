<?php

namespace Imee\Controller\Operate\Activity;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Payactivity\PayActivityAwardValidation;
use Imee\Service\Operate\Payactivity\PayActivityAwardService;

class PayactivityawardController extends BaseController
{
    /**
     * @var PayActivityAwardService $service
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new PayActivityAwardService();
    }

    /**
     * @page payactivityaward
     * @name 配置档位及奖励
     */
    public function mainAction()
    {
    }

    /**
     * @page payactivityaward
     * @point 列表
     */
    public function listAction()
    {
        $c = $this->params['c'] ?? '';
        if ($c == 'options') {
            return $this->outputSuccess($this->service->getOptions());
        }
        $list = $this->service->getList($this->params);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }

    /**
     * @page payactivityaward
     * @point 创建
     */
    public function createAction()
    {
        PayActivityAwardValidation::make()->validators($this->params);
        $this->service->create($this->params);
        return $this->outputSuccess();
    }

    /**
     * @page payactivityaward
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'payactivityaward', model_id = 'id')
     */
    public function modifyAction()
    {
        if (!isset($this->params['id']) || empty($this->params['id'])) {
            return $this->outputError(-1, 'ID错误');
        }
        PayActivityAwardValidation::make()->validators($this->params);
        $data = $this->service->modify($this->params);
        return $this->outputSuccess($data);
    }

    /**
     * @page payactivityaward
     * @point 删除
     * @logRecord(content = '删除', action = '2', model = 'payactivityaward', model_id = 'id')
     */
    public function deleteAction()
    {
        if (!isset($this->params['id']) || empty($this->params['id'])) {
            return $this->outputError(-1, 'ID错误');
        }
        $data = $this->service->delete($this->params['id']);
        return $this->outputSuccess($data);
    }

    /**
     * @page payactivityaward
     * @point 详情
     */
    public function infoAction()
    {
        $id = $this->params['id'] ?? 0;
        return $this->outputSuccess($this->service->info($id));
    }
}