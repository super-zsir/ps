<?php

namespace Imee\Controller\Operate\Activity;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Payactivity\PayActivityManageValidation;
use Imee\Service\Operate\Payactivity\PayActivityManageService;

class PayactivitymanageController extends BaseController
{
    /**
     * @var PayActivityManageService $service
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new PayActivityManageService();
    }

    /**
     * @page payactivitymanage
     * @name 充值活动管理
     */
    public function mainAction()
    {
    }

    /**
     * @page payactivitymanage
     * @point 列表
     */
    public function listAction()
    {
        $list = $this->service->getList($this->params);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }

    /**
     * @page payactivitymanage
     * @point 创建
     * @logRecord(content = '创建', action = '0', model = 'payactivitymanage', model_id = 'bigarea_id')
     */
    public function createAction()
    {
        PayActivityManageValidation::make()->validators($this->params);
        list($res, $msg) = $this->service->create($this->params);
        if (!$res) {
            return $this->outputError(-1, $msg);
        }
        return $this->outputSuccess(['bigarea_id' => $this->params['bigarea_id'], 'after_json' => $this->params]);
    }

    /**
     * @page payactivitymanage
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'payactivitymanage', model_id = 'bigarea_id')
     */
    public function modifyAction()
    {
        PayActivityManageValidation::make()->validators($this->params);
        list($res, $msg) = $this->service->modify($this->params);
        if (!$res) {
            return $this->outputError(-1, $msg);
        }
        return $this->outputSuccess(['after_json' => $this->params]);
    }
}