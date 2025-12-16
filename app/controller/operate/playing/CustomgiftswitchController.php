<?php

namespace Imee\Controller\Operate\Playing;

use Imee\Controller\BaseController;
use Imee\Service\Operate\Play\Customgift\RegionSwitchService;

class CustomgiftswitchController extends BaseController
{
    /** @var RegionSwitchService $service */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new RegionSwitchService();
    }

    /**
     * @page customgiftswitch
     * @name 定制礼物大区开关
     */
    public function mainAction()
    {
    }

    /**
     * @page customgiftswitch
     * @point 列表
     */
    public function listAction()
    {
        $res = $this->service->getList($this->params);
        return $this->outputSuccess($res['data'] ?? [], ['total' => $res['total'] ?? 0]);
    }

    /**
     * @page customgiftswitch
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'customgiftswitch', model_id = 'bigarea_id')
     */
    public function modifyAction()
    {
        [$result, $data] = $this->service->modify($this->params['bigarea_id'], $this->params['switch']);
        if (!$result) {
            return $this->outputError(-1, $data);
        }
        return $this->outputSuccess($data);
    }
}