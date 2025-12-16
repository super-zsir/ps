<?php

namespace Imee\Controller\Operate\User;

use Imee\Controller\BaseController;
use Imee\Service\Operate\User\UserListService;

class UserareainfosearchController extends BaseController
{
    public function onConstruct()
    {
        parent::onConstruct();
    }
    
    /**
     * @page userareainfosearch
     * @name 用户大区信息查询
     */
    public function mainAction()
    {
    }
    
    /**
     * @page userareainfosearch
     * @point 列表
     */
    public function listAction()
    {
        $list = UserListService::getListAndTotal($this->params, true);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }
}