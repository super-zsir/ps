<?php

namespace Imee\Controller\Operate;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Play\Redpacket\CodeRedPacketValidation;
use Imee\Service\Operate\Play\Redpacket\CodeRedPacketService;

class CoderedpacketswitchController extends BaseController
{
    /**
     * @var CodeRedPacketService
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new CodeRedPacketService();
    }
    
    /**
	 * @page coderedpacketswitch
	 * @name 口令红包大区开关
	 */
    public function mainAction()
    {
    }
    
    /**
	 * @page coderedpacketswitch
	 * @point 列表
	 */
    public function listAction()
    {
        $list = $this->service->getBigAreaList($this->params);

        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }
    
    /**
	 * @page coderedpacketswitch
	 * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'coderedpacketswitch', model_id = 'id')
	 */
    public function modifyAction()
    {
        CodeRedPacketValidation::make()->validators($this->params);
        $this->service->modifyBigAreaSwitch($this->params);

        return $this->outputSuccess(['after_json' => $this->params]);
    }

}