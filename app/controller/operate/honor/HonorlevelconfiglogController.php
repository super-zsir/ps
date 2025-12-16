<?php

namespace Imee\Controller\Operate\Honor;

use Imee\Comp\Common\Fixed\Csv;
use Imee\Controller\BaseController;
use Imee\Helper\Traits\ImportTrait;
use Imee\Models\Xs\XsUserHonorLevelSendRecord;
use Imee\Service\Operate\Honor\HonorLevelConfigLogService;

class HonorlevelconfiglogController extends BaseController
{
    use ImportTrait;

    /**
     * @var HonorLevelConfigLogService $service
     */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new HonorLevelConfigLogService();
    }

    /**
     * @page honorlevelconfiglog
     * @name 荣誉等级下发
     */
    public function mainAction()
    {
    }

    /**
     * @page  honorlevelconfiglog
     * @point 列表
     */
    public function listAction()
    {
        $params = $this->params;
        $c = trim($params['c'] ?? '');
        switch ($c) {
            case 'info':
                return $this->outputSuccess($this->service->getInfo($this->params));
            default:
                $data = $this->service->getListAndTotal($this->params);
                return $this->outputSuccess($data['data'], ['total' => $data['total']]);
        }
    }

    /**
     * @page  honorlevelconfiglog
     * @point 创建
     * @logRecord(content = '创建', action = '0', model = 'honorlevelconfiglog', model_id = 'id')
     */
    public function createAction()
    {
        list($flg, $rec) = $this->service->add($this->params, $this->uid);
        return $flg ? $this->outputSuccess($rec) : $this->outputError(-1, $rec);
    }

    /**
     * @page  honorlevelconfiglog
     * @point 批量导入
     */
    public function createBatchAction()
    {
        if (($this->params['c'] ?? '') == 'tpl') {
            (new Csv())->exportToCsv(array_values(XsUserHonorLevelSendRecord::uploadFields()), [], 'honorLevelConfigLog');
            exit;
        }

        [$success, $msg, $data] = $this->uploadCsv(array_keys(XsUserHonorLevelSendRecord::uploadFields()));
        if (!$success) {
            return $this->outputError(-1, $msg);
        }
        list($flg, $rec) = $this->service->addBatch($data['data'] ?? [], $this->uid);
        return $flg ? $this->outputSuccess($rec) : $this->outputError(-1, $rec);
    }
}