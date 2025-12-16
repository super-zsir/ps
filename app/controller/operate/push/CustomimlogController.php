<?php

namespace Imee\Controller\Operate\Push;

use Imee\Controller\BaseController;
use Imee\Service\Operate\Push\CustomImLogService;

class CustomimlogController extends BaseController
{
    /**
     * @var CustomImLogService
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new CustomImLogService();
    }

    /**
     * @page customimlog
     * @name 定制IM消息
     */
    public function mainAction()
    {
    }

    /**
     * @page customimlog
     * @point 列表
     */
    public function listAction()
    {
        $list = $this->service->getListAndTotal($this->params);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }

}