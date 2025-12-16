<?php

namespace Imee\Controller\Operate\Playing;

use Imee\Comp\Common\Fixed\Csv;
use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Play\Crash\OddsValidation;
use Imee\Service\Operate\Play\Crash\OddsService;

class CrashoddsController extends BaseController
{
    /** @var OddsService $service */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new OddsService();
    }
    
    /**
     * @page crashodds
     * @name Crash Odds
     */
    public function mainAction()
    {
    }

    /**
     * @page crashodds
     * @point 列表
     */
    public function listAction()
    {
        $res = $this->service->getTableList($this->params);
        return $this->outputSuccess($res['data'] ?? [], ['total' => $res['total'] ?? 0]);
    }
    
    /**
     * @page crashodds
     * @point 新增类型
     * @logRecord(content = '新增类型', action = '0', model = 'crashodds', model_id = 'tid')
     */
    public function createAction()
    {
        $this->params['data'] = $this->service->uploadOdds();
        OddsValidation::make()->validators($this->params);
        $data = $this->service->import($this->params, true);
        return $this->outputSuccess($data);
    }
    
    /**
     * @page crashodds
     * @point 导入覆盖
     * @logRecord(content = '导入覆盖', action = '1', model = 'crashodds', model_id = 'tid')
     */
    public function importAction()
    {
        $this->params['data'] = $this->service->uploadOdds();
        OddsValidation::make()->validators($this->params);
        $data = $this->service->import($this->params);
        return $this->outputSuccess($data);
    }
    
    /**
     * @page crashodds
     * @point 导出
     */
    public function exportAction()
    {
        $tid = $this->params['tid'] ?? -1;
        if ($tid == -1) {
            return $this->outputError(-1, 'table_id 必须存在');
        }
        $file = $this->service->export($this->params);
        (new Csv())->downLoadCsv($file);
    }
}