<?php

namespace Imee\Controller\Operate;

use Imee\Comp\Common\Export\Service\ExportService;
use Imee\Controller\BaseController;
use Imee\Export\Operate\FamilyMemberExport;
use Imee\Service\Operate\FamilyService;

class FamilymemberController extends BaseController
{
    /**
     * @var FamilyService
     */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new FamilyService();
    }

    /**
     * @page familymember
     * @name 运营系统-家族成员管理
     */
    public function mainAction()
    {
    }

    /**
     * @page  familymember
     * @point 列表
     */
    public function listAction()
    {
        if (empty($this->params['id'])) {
            return $this->outputError(-1, 'id必传');
        }
        $this->params['fid'] = $this->params['id'];
        $res = $this->service->getMemberListAndTotal($this->params);
        return $this->outputSuccess($res['data'] ?? [], ['total' => $res['total'] ?? 0]);
    }

    /**
     * @page  familymember
     * @point 移除
     * @logRecord(content = '移除', action = '2', model = 'familymember', model_id = 'id')
     */
    public function removeAction()
    {
        if (empty($this->params['id'])) {
            return $this->outputError(-1, 'id必传');
        }
        [$res, $msg] = $this->service->removeMember($this->params);
        if (!$res) {
            return $this->outputError(-1, $msg);
        }
        return $this->outputSuccess([]);
    }

    /**
     * @page  familymember
     * @point 导出
     */
    public function exportAction()
    {
        $this->params['guid'] = 'familymember';
        ExportService::addTask($this->uid, 'familymember.xlsx', [FamilyMemberExport::class, 'export'], $this->params, '家族成员列表');
        ExportService::showHtml();

        return $this->outputSuccess();
    }
}