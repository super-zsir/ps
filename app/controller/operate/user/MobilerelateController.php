<?php

namespace Imee\Controller\Operate\User;

use Imee\Controller\BaseController;
use Imee\Service\Operate\User\MobileRelateService;
use Imee\Service\Operate\User\MobileWhiteListService;

class MobilerelateController extends BaseController
{
    /**
     * @var MobileRelateService $service
     */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new MobileRelateService();
    }

    /**
     * @page mobilerelate
     * @name 用户管理-手机关联账号
     */
    public function mainAction()
    {
    }

    /**
     * @page  mobilerelate
     * @point 列表
     */
    public function listAction()
    {
        $res = $this->service->getListAndTotal($this->params);
        return $this->outputSuccess($res['data'] ?? [], ['total' => $res['total'] ?? 0]);
    }


}