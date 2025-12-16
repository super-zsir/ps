<?php

namespace Imee\Controller\Operate;

use Imee\Controller\BaseController;
use Imee\Service\Operate\UserLogInstructionService;

class UserloginstructionController extends BaseController
{
    /**
     * @var UserLogInstructionService $service
     */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new UserLogInstructionService();
    }

    /**
     * @page userloginstruction
     * @name 上传日志
     */
    public function mainAction()
    {
    }

    /**
     * @page  userloginstruction
     * @point 列表
     */
    public function listAction()
    {
        $data = $this->service->getListAndTotal($this->params);
        return $this->outputSuccess($data['data'], ['total' => $data['total']]);
    }

    /**
     * @page  userloginstruction
     * @point 创建
     * @logRecord(content = '创建', action = '0', model = 'userloginstruction', model_id = 'id')
     */
    public function createAction()
    {
        list($flg, $rec) = $this->service->add($this->params);
        return $flg ? $this->outputSuccess($rec) : $this->outputError(-1, $rec);
    }

}