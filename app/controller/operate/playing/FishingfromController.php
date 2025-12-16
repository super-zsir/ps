<?php

namespace Imee\Controller\Operate\Playing;

use Imee\Comp\Common\Fixed\Csv;
use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Play\Fishing\FishingFromValidation;
use Imee\Service\Operate\Play\Fishing\FishingFromService;

class FishingfromController extends BaseController
{
    /** @var FishingFromService $service */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new FishingFromService();
    }

    /**
     * @page fishingfrom
     * @name Fishing From
     */
    public function mainAction()
    {
    }

    /**
     * @page fishingfrom
     * @point 列表
     */
    public function listAction()
    {
        return $this->outputSuccess($this->service->getList());
    }

    /**
     * @page fishingfrom
     * @point 导入覆盖
     * @logRecord(content = '导入覆盖', action = '1', model = 'fishingfrom', model_id = 'id')
     */
    public function importAction()
    {
        $this->params['data'] = $this->service->uploadOdds();
        FishingFromValidation::make()->validators($this->params);
        $data = $this->service->import($this->params);
        return $this->outputSuccess($data);
    }

    /**
     * @page fishingfrom
     * @point 导出
     */
    public function exportAction()
    {
        $file = $this->service->export($this->params);
        (new Csv())->downLoadCsv($file);
    }
}