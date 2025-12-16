<?php

namespace Imee\Controller\Operate\Activity;

use Imee\Comp\Common\Export\Service\ExportService;
use Imee\Controller\BaseController;
use Imee\Export\Operate\Activity\LuckPlayIssuedAwardExport;
use Imee\Service\Operate\Activity\ActivityLuckGamePlayService;

class LuckplayissuedawardsearchController extends BaseController
{
    /**
     * @var ActivityLuckGamePlayService
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new ActivityLuckGamePlayService();
    }

    /**
     * @page luckplayissuedawardsearch
     * @name 幸运玩法发奖记录
     */
    public function mainAction()
    {
    }

    /**
     * @page luckplayissuedawardsearch
     * @point 列表
     */
    public function listAction()
    {
        $list = $this->service->getAwardHistory($this->params);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }

    /**
     * @page luckplayissuedawardsearch
     * @point 导出
     */
    public function exportAction()
    {
        $this->params['guid'] = 'luckplayissuedawardsearch';
        ExportService::addTask($this->uid, 'luckplayissuedawardsearch.xlsx', [LuckPlayIssuedAwardExport::class, 'export'], $this->params, '幸运玩法发奖记录导出');
        ExportService::showHtml();

        return $this->outputSuccess();
    }
}