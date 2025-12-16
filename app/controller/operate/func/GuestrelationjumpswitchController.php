<?php

namespace Imee\Controller\Operate\Func;

use Imee\Controller\BaseController;
use Imee\Models\Xs\XsBigarea;
use Imee\Service\Operate\ShowOriginUidSwitch;

class GuestrelationjumpswitchController extends BaseController
{
    /**
     * @var ShowOriginUidSwitch
     */
    private $service;

    private $type;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new ShowOriginUidSwitch();
        $this->type = XsBigarea::GUEST_RELATION_JUMP_SWITCH;
    }

    /**
     * @page guestrelationjumpswitch
     * @name 客态个人主页粉丝跳转管理
     */
    public function mainAction()
    {
    }

    /**
     * @page guestrelationjumpswitch
     * @point 列表
     */
    public function listAction()
    {
        $data = $this->service->getList($this->type);
        return $this->outputSuccess($data['data'] ?? [], ['total' => $data['total'] ?? 0]);
    }

    /**
     * @page guestrelationjumpswitch
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'guestrelationjumpswitch', model_id = 'bigarea_id')
     */
    public function modifyAction()
    {
        $switch = $this->params['guest_relation_jump_switch'] ?? 0;
        $id = $this->params['bigarea_id'] ?? 0;
        if (empty($id)) {
            return $this->outputError(-1, 'ID错误');
        }
        [$res, $msg] = $this->service->edit($switch, $id, $this->type);
        if (!$res) {
            return $this->outputError(-1, $msg);
        }
        return $this->outputSuccess($this->params);
    }
}