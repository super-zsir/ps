<?php

namespace Imee\Controller\Operate\Cp;

use Imee\Controller\BaseController;
use Imee\Export\Operate\Cp\RelationIncPropCardBuyRecordExport;
use Imee\Models\Xs\XsPropCardConfig;
use Imee\Service\Operate\Cp\RelationIncPropCardBuyRecordService;

class RelationincpropcardbuyrecordController extends BaseController
{
    /**
     * @var RelationIncPropCardBuyRecordService $service
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new RelationIncPropCardBuyRecordService();
    }
    
    /**
     * @page relationincpropcardbuyrecord
     * @name 关系增值道具购买记录
     */
    public function mainAction()
    {
    }
    
    /**
     * @page relationincpropcardbuyrecord
     * @point 列表
     */
    public function listAction()
    {
        $this->params['type'] = XsPropCardConfig::TYPE_PK_PROP_CARD_INTIMATE_RELATION_ICON;
        $list = $this->service->getListAndTotal($this->params);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }

    /**
     * @page relationincpropcardbuyrecord
     * @point 导出
     */
    public function exportAction()
    {
        $this->params['type'] = XsPropCardConfig::TYPE_PK_PROP_CARD_INTIMATE_RELATION_ICON;
        return $this->syncExportWork('relationIncPropCardBuyRecordExport', RelationIncPropCardBuyRecordExport::class, $this->params);
    }


}