<?php

namespace Imee\Controller\Operate\Relieveforbiddencard;

use Imee\Controller\BaseController;
use Imee\Export\Operate\Relieveforbiddencard\RelieveForbiddenCardUseRecordExport;
use Imee\Service\Operate\Relieveforbiddencard\RelieveForbiddenCardUseRecordService;

class RelieveforbiddencarduserecordController extends BaseController
{
    /**
     * @var RelieveForbiddenCardUseRecordService $service
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new RelieveForbiddenCardUseRecordService();
    }
    
    /**
     * @page relieveforbiddencarduserecord
     * @name 解封卡使用记录
     */
    public function mainAction()
    {
    }

    /**
     * @page relieveforbiddencarduserecord
     * @point 列表
     */
    public function listAction()
    {
        $list = $this->service->getList($this->params);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }

    /**
     * @page relieveforbiddencarduserecord
     * @point 导出
     */
    public function exportAction()
    {
        return $this->syncExportWork('relieveForbiddenCardUseRecordExport', RelieveForbiddenCardUseRecordExport::class, $this->params);
    }
}