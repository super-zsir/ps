<?php

namespace Imee\Controller\Super;

use Imee\Controller\BaseController;
use Imee\Service\Super\SuperService;

/**
 * 超管
 */
class SuperaccountController extends BaseController
{
    /**
     * @page superaccount
     * @name 超管系统-超管账号
     */
    public function mainAction()
    {
    }

    /**
     * @page  superaccount
     * @point 列表
     */
    public function listAction()
    {
        $data = SuperService::getInstance()->accountList($this->params);
        return $this->outputSuccess($data["data"], ["total" => $data["total"]]);
    }

    /**
     * @page  superaccount
     * @point 创建
     */
    public function createAction()
    {
        $data = SuperService::getInstance()->addAccount($this->params);
        if (isset($data['msg'])) {
            return $this->outputError(-1, $data['msg']);
        }
        return $this->outputSuccess();
    }

    /**
     * @page  superaccount
     * @point 绑定
     */
    public function modifyAction()
    {
        $data = SuperService::getInstance()->bind($this->params);
        if (isset($data['msg'])) {
            return $this->outputError(-1, $data['msg']);
        }
        return $this->outputSuccess();
    }
}