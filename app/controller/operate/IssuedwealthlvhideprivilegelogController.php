<?php

namespace Imee\Controller\Operate;

use Imee\Controller\BaseController;
use Imee\Export\Operate\IssuedWealthLvHidePrivilegeLogExport;
use Imee\Service\Operate\IssuedWealthLvHidePrivilegeService;
use Imee\Comp\Common\Export\Service\ExportService;

class IssuedwealthlvhideprivilegelogController extends BaseController
{
    /** @var IssuedWealthLvHidePrivilegeService */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new IssuedWealthLvHidePrivilegeService();
    }

    /**
     * @page issuedwealthlvhideprivilegelog
     * @name 财富等级隐藏权益-明细列表
     */
    public function mainAction()
    {
    }

    /**
     * @page issuedwealthlvhideprivilegelog
     * @point 列表
     */
    public function listAction()
    {
        $params = $this->trimParams($this->params);
        $result = $this->service->getLogList($params);
        return $this->outputSuccess($result['data'] ?? [], ['total' => $result['total'] ?? []]);
    }

    /**
     * @page issuedwealthlvhideprivilegelog
     * @point 导出
     */
    public function exportAction()
    {
        $this->params['guid'] = 'issuedwealthlvhideprivilegelog';
        ExportService::addTask(
            $this->uid,
            'issuedwealthlvhideprivilegelog.xlsx',
            [IssuedWealthLvHidePrivilegeLogExport::class, 'export'],
            $this->params,
            '财富等级隐藏权益-明细列表导出'
        );
        ExportService::showHtml();

        return $this->outputSuccess();
    }
}
