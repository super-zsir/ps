<?php

namespace Imee\Controller\Operate;

use Imee\Controller\BaseController;
use Imee\Service\Operate\InviteCodeService;

class InvitecodeController extends BaseController
{
    /**
     * @var InviteCodeService
     */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new InviteCodeService();
    }

    /**
     * @page invitecode
     * @name 邀请码
     */
    public function mainAction()
    {
    }

    /**
     * @page invitecode
     * @point 列表
     */
    public function listAction()
    {
        $res = $this->service->getListAndTotal($this->params);
        return $this->outputSuccess($res['data'] ?? [], ['total' => $res['total'] ?? 0]);
    }

    /**
     * @page invitecode
     * @point 创建
     * @logRecord(content = '创建', action = '0', model = 'invitecode', model_id = 'id')
     */
    public function createAction()
    {
        if (empty($this->params['uid'])) {
            return $this->outputError(-1, 'uid 必须');
        }
        $data = $this->service->create($this->params['uid'], $this->params['admin_id']);
        return $this->outputSuccess($data);
    }
}