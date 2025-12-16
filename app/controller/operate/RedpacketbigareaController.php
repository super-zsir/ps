<?php

namespace Imee\Controller\Operate;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Play\Redpacket\RedPacketValidation;
use Imee\Service\Operate\Play\Redpacket\RedPacketService;

class RedpacketbigareaController extends BaseController
{
    /**
     * @var RedPacketService
     */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new RedPacketService();
    }

    /**
     * @page redpacketbigarea
     * @name 普通红包大区开关
     */
    public function mainAction()
    {
    }

    /**
     * @page redpacketbigarea
     * @point 列表
     */
    public function listAction()
    {
        $res = $this->service->getBigareaList($this->params);
        return $this->outputSuccess($res['data'] ?? [], ['total' => $res['total'] ?? 0]);
    }

    /**
     * @page redpacketbigarea
     * @point 编辑
     * @logRecord(content = '修改', action = '1', model = 'redpacketbigarea', model_id = 'id')
     */
    public function modifyAction()
    {
        RedPacketValidation::make()->validators($this->params);
        $this->service->modifyBigAreaSwitch($this->params);

        return $this->outputSuccess(['after_json' => $this->params]);
    }
}