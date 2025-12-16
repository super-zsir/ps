<?php

namespace Imee\Controller\Operate;

use Imee\Controller\BaseController;
use Imee\Export\Operate\IssuedRoomStealthPrivilegeLogExport;
use Imee\Service\Operate\IssuedRoomStealthPrivilegeService;
use Imee\Comp\Common\Export\Service\ExportService;

class IssuedroomstealthprivilegelogController extends BaseController
{
    /** @var IssuedRoomStealthPrivilegeService */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new IssuedRoomStealthPrivilegeService();
    }

    /**
     * @page issuedroomstealthprivilegelog
     * @name 房间隐身权益-明细列表
     */
    public function mainAction()
    {
    }

    /**
     * @page issuedroomstealthprivilegelog
     * @point 列表
     */
    public function listAction()
    {
        $params = $this->trimParams($this->params);
        $result = $this->service->getLogList($params);
        return $this->outputSuccess($result['data'] ?? [], ['total' => $result['total'] ?? []]);
    }

    /**
     * @page issuedroomstealthprivilegelog
     * @point 导出
     */
    public function exportAction()
    {
        $this->params['guid'] = 'issuedroomstealthprivilegelog';
        ExportService::addTask(
            $this->uid,
            'issuedroomstealthprivilegelog.xlsx',
            [IssuedRoomStealthPrivilegeLogExport::class, 'export'],
            $this->params,
            '房间隐身权益-明细列表导出'
        );
        ExportService::showHtml();

        return $this->outputSuccess();
    }
} 