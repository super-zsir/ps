<?php

namespace Imee\Controller\Operate\User;

use Imee\Controller\BaseController;
use Imee\Service\Operate\User\DelayForbiddenTaskService;

class DelayforbiddentaskController extends BaseController
{
    /**
     * @var DelayForbiddenTaskService $service
     */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new DelayForbiddenTaskService();
    }


    /**
     * @page delayforbiddentask
     * @name 用户管理-用户延迟封禁
     */
    public function mainAction()
    {
    }

    /**
     * @page  delayforbiddentask
     * @point 列表
     */
    public function listAction()
    {
        $data = $this->service->getListAndTotal($this->params);
        return $this->outputSuccess($data['data'], ['total' => $data['total']]);
    }


}