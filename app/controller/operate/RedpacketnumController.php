<?php

namespace Imee\Controller\Operate;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Play\Redpacket\RedPacketNumValidation;
use Imee\Service\Operate\Play\Redpacket\RedPacketService;

class RedpacketnumController extends BaseController
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
     * @page redpacketnum
     * @name 普通红包数量配置
     */
    public function mainAction()
    {
    }

    /**
     * @page redpacketnum
     * @point 列表
     */
    public function listAction()
    {
        $res = $this->service->getCountList($this->params);
        return $this->outputSuccess($res['data'] ?? [], ['total' => $res['total'] ?? 0]);
    }

    /**
     * @page redpacketnum
     * @point 编辑
     * @logRecord(content = '修改', action = '1', model = 'redpacketnum', model_id = 'id')
     */
    public function modifyAction()
    {
        RedPacketNumValidation::make()->validators($this->params);
        $this->service->modifyNumber($this->params);
        return $this->outputSuccess(['after_json' => $this->params]);
    }
}