<?php

namespace Imee\Controller\Operate\Livesticker;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Livesticker\CustomStickerResourceAddValidation;
use Imee\Controller\Validation\Operate\Livesticker\CustomStickerResourceEditValidation;
use Imee\Service\Operate\Livesticker\CustomStickerResourceService;

class CustomstickerresourceController extends BaseController
{
    /**
     * @var CustomStickerResourceService
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new CustomStickerResourceService();
    }

    /**
     * @page customstickerresource
     * @name 贴纸素材管理
     */
    public function mainAction()
    {
    }

    /**
     * @page customstickerresource
     * @point 列表
     */
    public function listAction()
    {
        $list = $this->service->getList($this->params);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }

    /**
     * @page customstickerresource
     * @point 创建
     * @logRecord(content = '创建', action = '0', model = 'customstickerresource', model_id = 'id')
     */
    public function createAction()
    {
        CustomStickerResourceAddValidation::make()->validators($this->params);
        $res = $this->service->add($this->params);
        return $this->outputSuccess($res);
    }

    /**
     * @page customstickerresource
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'customstickerresource', model_id = 'id')
     */
    public function modifyAction()
    {
        CustomStickerResourceEditValidation::make()->validators($this->params);
        $res = $this->service->edit($this->params);
        return $this->outputSuccess($res);
    }
}