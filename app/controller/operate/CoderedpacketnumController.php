<?php

namespace Imee\Controller\Operate;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Play\Redpacket\CodeRedPacketNumValidation;
use Imee\Service\Operate\Play\Redpacket\CodeRedPacketService;

class CoderedpacketnumController extends BaseController
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
	 * @page coderedpacketnum
	 * @name 口令红包数量配置
	 */
    public function mainAction()
    {
    }
    
    /**
	 * @page coderedpacketnum
	 * @point 列表
	 */
    public function listAction()
    {
        $list = $this->service->getCountList($this->params);

        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }
    
    /**
	 * @page coderedpacketnum
	 * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'coderedpacketnum', model_id = 'id')
	 */
    public function modifyAction()
    {
        CodeRedPacketNumValidation::make()->validators($this->params);
        $this->service->modifyNumber($this->params);

        return $this->outputSuccess(['after_json' => $this->params]);
    }

}