<?php

namespace Imee\Controller\Log;

use Imee\Comp\Common\Log\Service\OperateLog;
use Imee\Controller\BaseController;

/**
 * 操作日志
 */
class OperatelogController extends BaseController
{
    /**
     * @var OperateLog $service
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new OperateLog();
    }

    /**
     * @page operatelog
     * @name 操作日志
     */
    public function mainAction()
    {
    }

    /**
     * @page  operatelog
     * @point 列表
     */
    public function listAction()
    {
        $data = $this->service->getListAndTotal(
            $this->params, 'id desc', $this->params['page'], $this->params['limit']
        );
        return $this->outputSuccess($data["data"], ["total" => $data["total"]]);
    }
}
