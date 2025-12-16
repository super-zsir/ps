<?php

namespace Imee\Controller\Operate\Linkjump;

use Imee\Controller\BaseController;
use Imee\Service\Operate\Linkjump\OutLinkJumpRegionSwitchService;

class OutlinkjumpregionswitchController extends BaseController
{
    /**
     * @var OutLinkJumpRegionSwitchService
     */
    private $service;
    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new OutLinkJumpRegionSwitchService();
    }

    /**
     * @page outlinkjumpregionswitch
     * @name 站外链接跳转设置-大区开关
     */
    public function mainAction()
    {
    }

    /**
     * @page outlinkjumpregionswitch
     * @point 列表
     */
    public function listAction()
    {
        $res = $this->service->getList($this->params);
        return $this->outputSuccess($res['data'] ?? [], ['total' => $res['total'] ?? 0]);
    }

    /**
     * @page outlinkjumpregionswitch
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'outlinkjumpregionswitch', model_id = 'id')
     */
    public function modifyAction()
    {
        [$result, $data] = $this->service->modify($this->params['id'], $this->params['switch']);
        if (!$result) {
            return $this->outputError(-1, $data);
        }
        return $this->outputSuccess($data);
    }
}
