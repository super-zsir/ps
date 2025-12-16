<?php

namespace Imee\Controller\Operate;

use Imee\Comp\Common\Export\Service\ExportService;
use Imee\Controller\BaseController;
use Imee\Export\Operate\FamilyExport;
use Imee\Service\Operate\FamilyService;

class FamilyController extends BaseController
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
     * @page family
     * @name 运营系统-家族管理
     */
    public function mainAction()
    {
    }

    /**
     * @page  family
     * @point 列表
     */
    public function listAction()
    {
        $res = $this->service->getListAndTotal($this->params);
        return $this->outputSuccess($res['data'] ?? [], ['total' => $res['total'] ?? 0]);
    }

    /**
     * @page  family
     * @point 编辑
     * @logRecord(content = '编辑', action = '1', model = 'family', model_id = 'id')
     */
    public function modifyAction()
    {
        if (empty($this->params['id'])) {
            return $this->outputError(-1, 'id必传');
        }
        $this->params['fid'] = $this->params['id'];
        [$res, $msg] = $this->service->modify($this->params);
        if (!$res) {
            return $this->outputError(-1, $msg);
        }
        return $this->outputSuccess([]);
    }

    /**
     * @page  family
     * @point 解散
     * @logRecord(content = '解散', action = '2', model = 'family', model_id = 'id')
     */
    public function dismissAction()
    {
        if (empty($this->params['id'])) {
            return $this->outputError(-1, 'id必传');
        }
        $this->params['fid'] = $this->params['id'];
        [$res, $msg] = $this->service->dismiss($this->params);
        if (!$res) {
            return $this->outputError(-1, $msg);
        }
        return $this->outputSuccess([]);
    }

    /**
     * @page  family
     * @point 等级
     * @logRecord(content = '等级', action = '1', model = 'family', model_id = 'fid')
     */
    public function levelAction()
    {
        if (empty($this->params['id'])) {
            return $this->outputError(-1, 'id必传');
        }
        $this->params['fid'] = $this->params['id'];
        [$res, $msg] = $this->service->level($this->params);
        if (!$res) {
            return $this->outputError(-1, $msg);
        }
        return $this->outputSuccess([]);
    }

    /**
     * @page  family
     * @point 等级记录
     */
    public function levelHistoryAction()
    {
        if (empty($this->params['id'])) {
            return $this->outputError(-1, 'id必传');
        }
        $this->params['fid'] = $this->params['id'];
        $list = $this->service->levelHistory($this->params, $this->params['page'] ?? 1, $this->params['limit'] ?? 15);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total' ?? 0]]);
    }

    /**
     * @page  family
     * @point 导出
     */
    public function exportAction()
    {
        $this->params['guid'] = 'family';
        ExportService::addTask($this->uid, 'family.xlsx', [FamilyExport::class, 'export'], $this->params, '家族列表');
        ExportService::showHtml();

        return $this->outputSuccess();
    }
}