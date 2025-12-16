<?php

namespace Imee\Controller\Log;

use Imee\Comp\Common\Log\Service\LoginService;
use Imee\Controller\BaseController;

/**
 * 登录日志
 */
class LoginlogController extends BaseController
{
    public function onConstruct()
    {
        parent::onConstruct();
    }

    /**
     * @page loginlog
     * @name 登录日志
     */
    public function mainAction()
    {
    }

    /**
     * @page  loginlog
     * @point 列表
     */
    public function listAction()
    {
        $data = LoginService::getList($this->params);
        return $this->outputSuccess($data["data"], ["total" => $data["total"]]);
    }

    /**
     * @page  loginlog
     * @point 导出
     */
    public function exportAction()
    {
        return $this->syncExportWork('loginLogExport', LoginService::class, $this->params);
    }
}