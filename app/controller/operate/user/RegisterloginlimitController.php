<?php

namespace Imee\Controller\Operate\User;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\User\RegisterLoginLimitValidation;
use Imee\Service\Operate\User\RegisterLoginLimitService;

class RegisterloginlimitController extends BaseController
{
    /**
     * @var RegisterLoginLimitService
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new RegisterLoginLimitService();
    }

    /**
     * @page registerloginlimit
     * @name 注册登录账号数限制管理
     */
    public function mainAction()
    {
    }

    /**
     * @page registerloginlimit
     * @point 列表
     */
    public function listAction()
    {
        $list = $this->service->getList($this->params);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }

    /**
     * @page registerloginlimit
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'registerloginlimit', model_id = 'id')
     */
    public function modifyAction()
    {
        RegisterLoginLimitValidation::make()->validators($this->params);
        $this->service->edit($this->params);
        return $this->outputSuccess(['after_json' => $this->params]);
    }
}