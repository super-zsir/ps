<?php

namespace Imee\Controller\Operate\Livesticker;

use Imee\Controller\BaseController;
use Imee\Service\Operate\Livesticker\RegionSwitchService;

class CustomstickerregionswitchController extends BaseController
{
    /** @var RegionSwitchService $service */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new RegionSwitchService();
    }

    /**
     * @page customstickerregionswitch
     * @name 贴纸大区开关
     */
    public function mainAction()
    {
    }

    /**
     * @page customstickerregionswitch
     * @point 列表
     */
    public function listAction()
    {
        $res = $this->service->getList($this->params);
        return $this->outputSuccess($res['data'] ?? [], ['total' => $res['total'] ?? 0]);
    }

    /**
     * @page customstickerregionswitch
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'customstickerregionswitch', model_id = 'bigarea_id')
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