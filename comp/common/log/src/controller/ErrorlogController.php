<?php

namespace Imee\Controller\Log;

use Imee\Comp\Common\Log\Service\ErrorLogService;
use Imee\Controller\BaseController;

/**
 * 巡检日志
 */
class ErrorlogController extends BaseController
{
    public function onConstruct()
    {
        parent::onConstruct();
    }

    /**
     * @page errorlog
     * @name 巡检日志
     */
    public function mainAction()
    {
    }

    /**
     * @page  errorlog
     * @point 列表
     */
    public function listAction()
    {
        $data = ErrorLogService::getList($this->params);
        return $this->outputSuccess($data["data"], ["total" => $data["total"]]);
    }
}