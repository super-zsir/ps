<?php

namespace Imee\Controller\Operate;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\VideoConfigFileManageAddValidation;
use Imee\Service\Operate\VideoConfigFileManageService;

class VideoconfigfilemanageController extends BaseController
{
    /**
     * @var VideoConfigFileManageService $service
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new VideoConfigFileManageService();
    }
    
    /**
     * @page videoconfigfilemanage
     * @name 配置文件管理
     */
    public function mainAction()
    {
    }
    
    /**
     * @page videoconfigfilemanage
     * @point 列表
     */
    public function listAction()
    {
        $res = $this->service->getList($this->params);
        return $this->outputSuccess($res['data'] ?? [], ['total' => $res['total'] ?? 0]);
    }
    
    /**
     * @page videoconfigfilemanage
     * @point 创建
     * @logRecord(content = '创建', action = '0', model = 'videoconfigfilemanage', model_id = 'id')
     */
    public function createAction()
    {
        VideoConfigFileManageAddValidation::make()->validators($this->params);
        $data = $this->service->save($this->params);
        return $this->outputSuccess($data);
    }

    /**
     * @page videoconfigfilemanage
     * @point 编辑
     * @logRecord(content = '编辑', action = '1', model = 'videoconfigfilemanage', model_id = 'id')
     */
    public function modifyAction()
    {
        VideoConfigFileManageAddValidation::make()->validators($this->params);
        $data = $this->service->save($this->params, false);
        return $this->outputSuccess($data);
    }

    /**
     * @page videoconfigfilemanage
     * @point 删除
     * @logRecord(content = '删除', action = '2', model = 'videoconfigfilemanage', model_id = 'id')
     */
    public function deleteAction()
    {
        $data = $this->service->delete($this->params);
        return $this->outputSuccess($data);
    }
}