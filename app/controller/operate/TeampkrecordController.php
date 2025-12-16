<?php

namespace Imee\Controller\Operate;

use Imee\Comp\Common\Export\Service\ExportService;
use Imee\Controller\BaseController;
use Imee\Export\Team\TeamPkRecordExport;
use Imee\Service\Rpc\PsService;

class TeampkrecordController extends BaseController
{
    protected function onConstruct()
    {
        parent::onConstruct();
    }

    /**
     * @page teampkrecord
     * @name 运营系统-房间Pk数据
     */
    public function mainAction(){}

    /**
     * @page teampkrecord
     * @point  列表
     */
    public function listAction()
    {
        list($res, $msg, $data) = (new PsService())->getTeamPkRecordList($this->params);
        if (!$res) {
            return $this->outputError(-1, $msg);
        }
        return $this->outputSuccess($data['data'], ['total' => $data['total']]);
    }

    /**
     * @page teampkrecord
     * @point 导出
     */
    public function exportAction()
    {
        $this->params['guid'] = 'teampkrecord';
        ExportService::addTask($this->uid, 'teampkrecord.xlsx', [TeamPkRecordExport::class, 'export'], $this->params, '房间Pk数据导出');
        ExportService::showHtml();

        return $this->outputSuccess();
    }
}