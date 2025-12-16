<?php

namespace Imee\Controller\Operate;

use Imee\Controller\BaseController;
use Imee\Service\Operate\Play\Teenpatti\RegionSwitchService;

class TeenpattiplayregionswitchController extends BaseController
{
    /** @var RegionSwitchService $service */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new RegionSwitchService();
    }

    /**
     * @page teenpattiplayregionswitch
     * @name 玩法管理-Teen Patti玩法配置-Teen Patti玩法大区开关
     */
    public function mainAction()
    {
    }

    /**
     * @page teenpattiplayregionswitch
     * @point 列表
     */
    public function listAction()
    {
        $res = $this->service->getList($this->params, 'id asc', 1, 20);
        return $this->outputSuccess($res['data'] ?? [], ['total' => $res['total'] ?? 0]);
    }

    /**
     * @page  teenpattiplayregionswitch
     * @point 修改
     * @logRecord(content = "修改TeenPatti大区开关", action = "1", model = "teenpattiplayregionswitch", model_id = "id")
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