<?php

namespace Imee\Controller\Operate\User;

use Imee\Controller\BaseController;
use Imee\Service\Operate\User\UserService;

class UserrelationlistController extends BaseController
{

    /**
     * @page userrelationlist
     * @name 用户管理-关联GID查询
     */
    public function mainAction()
    {

    }

    /**
     * @page userrelationlist
     * @point 关联GID查询
     */
    public function listAction()
    {
        //uid reason
        $service = new UserService();
        $data = $service->relationAccount($this->params);
        return $this->outputSuccess($data, array('total' => count($data)));
    }
}