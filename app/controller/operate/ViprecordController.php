<?php

namespace Imee\Controller\Operate;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Viprecord\ListValidation;
use Imee\Service\Operate\ViprecordService;

class ViprecordController extends BaseController
{
    /**
     * @var ViprecordService
     */
    private $service;
    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new ViprecordService;
    }

    /**
     * @page viprecord
     * @name VIP管理-VIP变更记录
     */
    public function mainAction()
    {
    }

    /**
     * @page viprecord
     * @point 列表
     */
    public function listAction()
    {
        $params = $this->trimParams($this->request->get());
        ListValidation::make()->validators($params);
        $result = $this->service->getList($params);
        return $this->outputSuccess($result['data'], array('total' => $result['total']));
    }
}
