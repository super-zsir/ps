<?php

namespace Imee\Controller\Operate\Face;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Face\UserFaceValidation;
use Imee\Service\Operate\Face\UserFaceService;

class UserfaceController extends BaseController
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
     * @page userface
     * @name 主播人脸库
     */
    public function mainAction()
    {
    }

    /**
     * @page userface
     * @point 列表
     */
    public function listAction()
    {
        $list = $this->service->getList($this->params);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }

    /**
     * @page userface
     * @point 更换图片
     * @logRecord(content = '更换图片', action = '1', model = 'userface', model_id = 'id')
     */
    public function replaceImageAction()
    {
        $this->params['type'] = UserFaceService::REPLACE_IMAGE_ACTION;
        UserFaceValidation::make()->validators($this->params);
        $data = $this->service->replace($this->params);
        return $this->outputSuccess($data);
    }

    /**
     * @page userface
     * @point 更换UID
     * @logRecord(content = '更换UID', action = '1', model = 'userface', model_id = 'id')
     */
    public function replaceUidAction()
    {
        $this->params['type'] = UserFaceService::REPLACE_UID_ACTION;
        UserFaceValidation::make()->validators($this->params);
        $data = $this->service->replace($this->params);
        return $this->outputSuccess($data);
    }

    /**
     * @page userface
     * @point 删除图片
     * @logRecord(content = '删除图片', action = '2', model = 'userface', model_id = 'id')
     */
    public function deleteAction()
    {
        $this->params['type'] = UserFaceService::DELETE_IMAGE_ACTION;
        UserFaceValidation::make()->validators($this->params);
        $data = $this->service->replace($this->params);
        return $this->outputSuccess($data);
    }
}