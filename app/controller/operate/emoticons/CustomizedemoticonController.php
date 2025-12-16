<?php

namespace Imee\Controller\Operate\Emoticons;

use Imee\Controller\BaseController;
use Imee\Service\Operate\Emoticons\CustomizedEmoticonService;

class CustomizedemoticonController extends BaseController
{
    /**
     * @var CustomizedEmoticonService $service
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new CustomizedEmoticonService();
    }
    
    /**
     * @page customizedemoticon
     * @name 定制表情配置
     */
    public function mainAction()
    {
    }
    
    /**
     * @page customizedemoticon
     * @point 列表
     */
    public function listAction()
    {
        $data = $this->service->getList($this->params);
        return $this->outputSuccess($data['data'] ?? [], ['total' => $data['total'] ?? 0]);
    }
    
    /**
     * @page customizedemoticon
     * @point 创建
     * @logRecord(content = '创建', action = '0', model = 'customizedemoticon', model_id = 'id')
     */
    public function createAction()
    {
        $data = $this->service->add($this->params);
        return $this->outputSuccess($data);
    }
    
    /**
     * @page customizedemoticon
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'customizedemoticon', model_id = 'id')
     */
    public function modifyAction()
    {
        $data = $this->service->edit($this->params);
        return $this->outputSuccess($data);
    }
    
    /**
     * @page customizedemoticon
     * @point 详情
     */
    public function infoAction()
    {
        $id = $this->params['id'] ?? 0;
        $data = $this->service->info($id);
        return $this->outputSuccess($data);
    }
}