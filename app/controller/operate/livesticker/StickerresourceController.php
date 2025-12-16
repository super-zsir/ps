<?php

namespace Imee\Controller\Operate\Livesticker;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Livesticker\StickerResourceAddValidation;
use Imee\Controller\Validation\Operate\Livesticker\StickerResourceEditValidation;
use Imee\Service\Operate\Livesticker\StickerResourceService;

class StickerresourceController extends BaseController
{
    /**
     * @var StickerResourceService
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new StickerResourceService();
    }

    /**
     * @page stickerresource
     * @name 特效素材管理
     */
    public function mainAction()
    {
    }

    /**
     * @page stickerresource
     * @point 列表
     */
    public function listAction()
    {
        $list = $this->service->getList($this->params);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }

    /**
     * @page stickerresource
     * @point 创建
     * @logRecord(content = '创建', action = '0', model = 'stickerresource', model_id = 'id')
     */
    public function createAction()
    {
        StickerResourceAddValidation::make()->validators($this->params);
        $res = $this->service->add($this->params);
        return $this->outputSuccess($res);
    }

    /**
     * @page stickerresource
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'stickerresource', model_id = 'id')
     */
    public function modifyAction()
    {
        StickerResourceEditValidation::make()->validators($this->params);
        $res = $this->service->edit($this->params);
        return $this->outputSuccess($res);
    }
}