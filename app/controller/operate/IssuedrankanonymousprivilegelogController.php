<?php

namespace Imee\Controller\Operate;

use Imee\Controller\BaseController;
use Imee\Export\Operate\IssuedRankAnonymousPrivilegeLogExport;
use Imee\Service\Operate\IssuedRankAnonymousPrivilegeService;
use Imee\Comp\Common\Export\Service\ExportService;

class IssuedrankanonymousprivilegelogController extends BaseController
{
    /** @var IssuedRankAnonymousPrivilegeService */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new IssuedRankAnonymousPrivilegeService();
    }

    /**
     * @page issuedrankanonymousprivilegelog
     * @name 贡献榜单匿名权益-明细列表
     */
    public function mainAction()
    {
    }

    /**
     * @page issuedrankanonymousprivilegelog
     * @point 列表
     */
    public function listAction()
    {
        $params = $this->trimParams($this->params);
        $result = $this->service->getLogList($params);
        return $this->outputSuccess($result['data'] ?? [], ['total' => $result['total'] ?? []]);
    }

    /**
     * @page issuedrankanonymousprivilegelog
     * @point 导出
     */
    public function exportAction()
    {
        $this->params['guid'] = 'issuedrankanonymousprivilegelog';
        ExportService::addTask(
            $this->uid,
            'issuedrankanonymousprivilegelog.xlsx',
            [IssuedRankAnonymousPrivilegeLogExport::class, 'export'],
            $this->params,
            '贡献榜单匿名权益-明细列表导出'
        );
        ExportService::showHtml();

        return $this->outputSuccess();
    }
}
