<?php

namespace Imee\Controller\Operate\Lighting;

use Imee\Comp\Common\Fixed\Csv;
use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Lighting\NameIdLightingLogValidation;
use Imee\Helper\Traits\ImportTrait;
use Imee\Models\Xs\XsNameIdLightingLog;
use Imee\Service\Operate\Lighting\NameIdLightingLogService;

class NameidlightinglogController extends BaseController
{
    use ImportTrait;

    /**
     * @var NameIdLightingLogService $service
     */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new NameIdLightingLogService();
    }

    /**
     * @page nameidlightinglog
     * @name 炫彩资源下发
     */
    public function mainAction()
    {
    }

    /**
     * @page  nameidlightinglog
     * @point 列表
     */
    public function listAction()
    {
        $data = $this->service->getListAndTotal($this->params);
        return $this->outputSuccess($data['data'], ['total' => $data['total']]);
    }

    /**
     * @page  nameidlightinglog
     * @point 创建
     * @logRecord(content = '创建', action = '0', model = 'nameidlightinglog', model_id = 'id')
     */
    public function createAction()
    {
        NameIdLightingLogValidation::make()->validators($this->params);
        list($flg, $rec) = $this->service->add($this->params, $this->uid);
        return $flg ? $this->outputSuccess($rec) : $this->outputError(-1, $rec);
    }

    /**
     * @page  nameidlightinglog
     * @point 批量导入
     */
    public function createBatchAction()
    {
        if (($this->params['c'] ?? '') == 'tpl') {
            (new Csv())->exportToCsv(array_values(XsNameIdLightingLog::uploadFields()), [], 'nameIdLightingLog');
            exit;
        }

        [$success, $msg, $data] = $this->uploadCsv(array_keys(XsNameIdLightingLog::uploadFields()));
        if (!$success) {
            return $this->outputError(-1, $msg);
        }
        foreach ($data['data'] as $k => $item) {
            if ($item['uid'] == '用户UID') {
                unset($data['data'][$k]);
                continue;
            }
            NameIdLightingLogValidation::make()->validators($item);
        }

        list($flg, $rec) = $this->service->addBatch($data['data'] ?? [], $this->uid);
        return $flg ? $this->outputSuccess($rec) : $this->outputError(-1, $rec);
    }


}