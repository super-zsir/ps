<?php

namespace Imee\Controller\Operate\Face;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Face\UserFaceAuditValidation;
use Imee\Service\Operate\Face\UserFaceService;

class UserfaceauditController extends BaseController
{
    /**
     * @var UserFaceService $service
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new UserFaceService();
    }

    /**
     * @page userfaceaudit
     * @name 人脸审核记录
     */
    public function mainAction()
    {
    }

    /**
     * @page userfaceaudit
     * @point 列表
     */
    public function listAction()
    {
        $list = $this->service->getAuditList($this->params);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }

    /**
     * @page userfaceaudit
     * @point 修改审核结果
     * @logRecord(content = '修改审核结果', action = '1', model = 'userfaceaudit', model_id = 'id')
     */
    public function modifyAction()
    {
        $this->params['type'] = UserFaceService::UPDATE_AUDIT_STATUS;
        UserFaceAuditValidation::make()->validators($this->params);
        $data = $this->service->replace($this->params);
        return $this->outputSuccess($data);
    }
}