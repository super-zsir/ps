<?php

namespace Imee\Controller\Operate\User;

use Imee\Controller\BaseController;
use Imee\Service\Operate\User\UserService;

class UseridcardlistController extends BaseController
{

    /**
     * @page useridcardlist
     * @name 用户管理-身份认证查询
     */
    public function mainAction()
    {

    }

    /**
     * @page useridcardlist
     * @point list
     */
    public function listAction()
    {
        //uid
        $service = new UserService();
        $data = $service->idcardIndex($this->params);
        return $this->outputSuccess($data, array('total' => count($data)));
    }
}