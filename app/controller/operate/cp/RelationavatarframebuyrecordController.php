<?php

namespace Imee\Controller\Operate\Cp;

use Imee\Controller\BaseController;
use Imee\Export\Operate\Cp\RelationIncPropCardBuyRecordExport;
use Imee\Models\Xs\XsPropCardConfig;
use Imee\Service\Operate\Cp\RelationIncPropCardBuyRecordService;

class RelationavatarframebuyrecordController extends BaseController
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
     * @page relationavatarframebuyrecord
     * @name 关系头像框购买记录
     */
    public function mainAction()
    {
    }
    
    /**
     * @page relationavatarframebuyrecord
     * @point 列表
     */
    public function listAction()
    {
        $this->params['type'] = XsPropCardConfig::TYPE_PK_PROP_CARD_RELATION_AVATAR_FRAME;
        $list = $this->service->getListAndTotal($this->params);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }

    /**
     * @page relationavatarframebuyrecord
     * @point 导出
     */
    public function exportAction()
    {
        $this->params['type'] = XsPropCardConfig::TYPE_PK_PROP_CARD_RELATION_AVATAR_FRAME;
        return $this->syncExportWork('relationIncPropCardBuyRecordExport', RelationIncPropCardBuyRecordExport::class, $this->params);
    }


}