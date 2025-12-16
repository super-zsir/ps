<?php

namespace Imee\Controller\Operate\Activity;

use Imee\Controller\BaseController;
use Imee\Service\Operate\Activity\ActivityAccountManageService;

class ActivityaccountmanageController extends BaseController
{
    /**
     * @var ActivityAccountManageService
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new ActivityAccountManageService();
    }

    /**
     * @page activityaccountmanage
     * @name 活动账户管理
     */
    public function mainAction()
    {
    }

    /**
     * @page activityaccountmanage
     * @point 列表
     */
    public function listAction()
    {
        $list = $this->service->getList();
        return $this->outputSuccess($list);
    }
}