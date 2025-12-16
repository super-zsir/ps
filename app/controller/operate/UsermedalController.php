<?php

namespace Imee\Controller\Operate;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Medal\UserMedalValidation;
use Imee\Service\Operate\Medal\UserMedalService;

class UsermedalController extends BaseController
{
    /**
     * @var UserMedalService
     */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new UserMedalService();
    }

    /**
     * @page usermedal
     * @name 运营系统-勋章-用户勋章管理
     */
    public function mainAction()
    {
    }

    /**
     * @page usermedal
     * @point 列表
     */
    public function listAction()
    {
        $res = $this->service->getList($this->params, $this->params['page'] ?? 1, $this->params['limit'] ?? 15);
        return $this->outputSuccess($res['data'] ?? [], ['total' => $res['total'] ?? 0]);
    }

    /**
     * @page usermedal
     * @point 扣除
     */
    public function lessAction()
    {
        UserMedalValidation::make()->validators($this->params);
        [$res, $msg] = $this->service->lessTime($this->params);
        if (!$res) {
            return $this->outputError('-1', $msg);
        }
        return $this->outputSuccess([]);
    }
}