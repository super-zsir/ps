<?php

namespace Imee\Controller\Ka;

use Imee\Controller\BaseController;
use Imee\Service\Domain\Service\Ka\UserService;

class KauserController extends BaseController
{
    /**
     * @var UserService
     */
    private $userService;

    public function onConstruct()
    {
        parent::onConstruct();

        $this->userService = new UserService();
    }

    /**
     * @page kauser
     * @name KA用户列表
     */
    public function mainAction()
    {
    }

    /**
     * @page kauser
     * @point 分配客服
     */
    public function allocationKfAction()
    {
        return $this->outputSuccess($this->userService->allocationKf($this->params));
    }

    /**
     * @page kauser
     * @point 建联
     */
    public function buildAlStatusAction()
    {
        return $this->outputSuccess($this->userService->buildAlStatus($this->params));
    }

    /**
     * @page kauser
     * @point 创建
     * @logRecord(content = '创建', action = '0', model = 'BmsKaUserList', model_id = 'uid')
     */
    public function createAction()
    {
        return $this->outputSuccess($this->userService->create($this->params));
    }
}
