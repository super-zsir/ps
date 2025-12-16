<?php

namespace Imee\Controller\Operate;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\ImageToLinkValidation;
use Imee\Service\Operate\ImageToLinkService;

class ImagetolinkController extends BaseController
{
    /**
     * @var ImageToLinkService
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new ImageToLinkService();
    }

    /**
     * @page imagetolink
     * @name 文件转链接工具
     */
    public function mainAction()
    {
    }

    /**
     * @page imagetolink
     * @point 列表
     */
    public function listAction()
    {
        $list = $this->service->getList($this->params);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }

    /**
     * @page imagetolink
     * @point 创建
     * @logRecord(content = '创建', action = '0', model = 'imagetolink', model_id = 'id')
     */
    public function createAction()
    {
        ImageToLinkValidation::make()->validators($this->params);
        $id = $this->service->add($this->params);
        return $this->outputSuccess(['id' => $id, 'after_json' => $this->params]);
    }

    /**
     * @page imagetolink
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'imagetolink', model_id = 'id')
     */
    public function modifyAction()
    {
        if (!isset($this->params['id']) || empty($this->params['id'])) {
            return $this->outputError(-1, '数据错误');
        }
        ImageToLinkValidation::make()->validators($this->params);
        $this->service->edit($this->params);
        return $this->outputSuccess(['after_json' => $this->params]);
    }

    /**
     * @page imagetolink
     * @point 复制
     * @logRecord(content = '复制', action = '0', model = 'imagetolink', model_id = 'id')
     */
    public function copyAction()
    {
        ImageToLinkValidation::make()->validators($this->params);
        $id = $this->service->add($this->params);
        return $this->outputSuccess(['id' => $id, 'after_json' => $this->params]);
    }
}