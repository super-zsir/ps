<?php

namespace Imee\Controller\Super;

use Imee\Controller\BaseController;
use Imee\Service\Super\SuperService;

/**
 * 巡管
 */
class PatrolaccountController extends BaseController
{
    /**
     * @page patrolaccount
     * @name 超管系统-巡管账号
     */
    public function mainAction()
    {
    }

    /**
     * @page  patrolaccount
     * @point 列表
     */
    public function listAction()
    {
        $data = SuperService::getInstance()->patrolAccountList($this->params);
        return $this->outputSuccess($data["data"], ["total" => $data["total"]]);
    }

    /**
     * @page  patrolaccount
     * @point 创建
     */
    public function createAction()
    {
        $data = SuperService::getInstance()->addPatrolAccount($this->params);
        if (isset($data['msg'])) {
            return $this->outputError(-1, $data['msg']);
        }
        return $this->outputSuccess();
    }

    /**
     * @page  patrolaccount
     * @point 绑定
     */
    public function modifyAction()
    {
        $data = SuperService::getInstance()->bindPatrol($this->params);
        if (isset($data['msg'])) {
            return $this->outputError(-1, $data['msg']);
        }
        return $this->outputSuccess();
    }
}