<?php

namespace Imee\Controller\Operate\Push;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Push\PushContentAddValidation;
use Imee\Controller\Validation\Operate\Push\PushContentEditValidation;
use Imee\Service\Operate\Push\PushService;

class PushcontentController extends BaseController
{
    /**
     * @var PushService
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new PushService();
    }

    /**
     * @page pushcontent
     * @name push文案管理
     */
    public function mainAction()
    {
    }

    /**
     * @page pushcontent
     * @point 列表
     */
    public function listAction()
    {
        $list = $this->service->getContentList($this->params);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }

    /**
     * @page pushcontent
     * @point 创建
     * @logRecord(content = '创建', action = '0', model = 'pushcontent', model_id = 'id')
     */
    public function createAction()
    {
        PushContentAddValidation::make()->validators($this->params);
        $this->service->addContent($this->params);
        return $this->outputSuccess(['after_json' => $this->params]);
    }

    /**
     * @page pushcontent
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'pushcontent', model_id = 'id')
     */
    public function modifyAction()
    {
        PushContentEditValidation::make()->validators($this->params);
        [$result, $msg] = $this->service->editContent($this->params);
        if (!$result) {
            return $this->outputError(-1, $msg);
        }
        return $this->outputSuccess(['after_json' => $this->params]);
    }

    /**
     * @page pushcontent
     * @point 删除
     * @logRecord(content = '删除', action = '2', model = 'pushcontent', model_id = 'id')
     */
    public function deleteAction()
    {
        $id = $this->params['id'] ?? 0;
        if (empty($id)) {
            return $this->outputError(-1, 'ID错误');
        }
        $this->service->deleteContent($id);
        return $this->outputSuccess();
    }
}