<?php


namespace Imee\Controller\Operate\Relieveforbiddencard;


use Imee\Controller\BaseController;
use Imee\Models\Xs\XsSendPropCardLog;
use Imee\Service\Operate\Relieveforbiddencard\RelieveForbiddenCardSendService;

class RelieveforbiddencardgiverecordController extends BaseController
{
    /**
     * @var RelieveForbiddenCardSendService $service
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new RelieveForbiddenCardSendService();
    }

    /**
     * @page relieveforbiddencardgiverecord
     * @name 解封卡赠送记录
     */
    public function mainAction()
    {
    }

    /**
     * @page relieveforbiddencardgiverecord
     * @point 列表
     */
    public function listAction()
    {
        $list = $this->service->getList($this->params,XsSendPropCardLog::SOURCE_GIVE);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }

}