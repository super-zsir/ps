<?php

namespace Imee\Controller\Operate;

use Imee\Comp\Common\Export\Service\ExportService;
use Imee\Controller\BaseController;
use Imee\Export\LuckyGiftDetailedExport;
use Imee\Service\Luckygift\DetailedService;

class LuckygiftdetailedController extends BaseController
{
    /** @var  DetailedService $service */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new DetailedService();
    }

    /**
     * @page luckygiftdetailed
     * @name 幸运礼物玩法配置-明细数据查询
     */
    public function mainAction()
    {
    }

    /**
     * @page luckygiftdetailed
     * @point 列表
     */
    public function listAction()
    {
        $data = $this->service->getList($this->params);
        return $this->outputSuccess($data['data'] ?? [], ['total' => $data['total'] ?? 0]);
    }

    /**
     * @page luckygiftdetailed
     * @point 创建
     */
    public function createAction()
    {
        return $this->outputSuccess([]);
    }

    /**
     * @page luckygiftdetailed
     * @point 编辑
     */
    public function modifyAction()
    {
        return $this->outputSuccess([]);
    }

    /**
     * @page luckygiftdetailed
     * @point 删除
     */
    public function deleteAction()
    {
        return $this->outputSuccess([]);
    }

    /**
     * @page luckygiftdetailed
     * @point 导出
     */
    public function exportAction()
    {
        if (empty($this->params['uid']) || !isset($this->params['uid'])) {
            return $this->outputError(-1, '由于数据量过大，必须指定uid进行导出');
        }

        $this->params['guid'] = 'luckygiftdetailed';
        ExportService::addTask($this->uid, 'luckygiftdetailed.xlsx', [LuckyGiftDetailedExport::class, 'export'], $this->params, '幸运礼物明细查询导出');
        ExportService::showHtml();

        return $this->outputSuccess();
    }
}