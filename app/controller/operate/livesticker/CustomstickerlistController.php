<?php

namespace Imee\Controller\Operate\Livesticker;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Livesticker\StickerListValidation;
use Imee\Service\Operate\Livesticker\CustomStickerListService;

class CustomstickerlistController extends BaseController
{
    /**
     * @var CustomStickerListService
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new CustomStickerListService();
    }

    /**
     * @page customstickerlist
     * @name 贴纸列表管理
     */
    public function mainAction()
    {
    }

    /**
     * @page customstickerlist
     * @point 列表
     */
    public function listAction()
    {
        $list = $this->service->getList($this->params);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }

    /**
     * @page customstickerlist
     * @point 创建
     */
    public function createAction()
    {
        StickerListValidation::make()->validators($this->params);
        $this->service->add($this->params);
        return $this->outputSuccess();
    }

    /**
     * @page customstickerlist
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'customstickerlist', model_id = 'manage_id')
     */
    public function modifyAction()
    {
        if (!isset($this->params['manage_id']) && empty($this->params['manage_id'])) {
            return $this->outputError(-1, 'ID错误');
        }
        StickerListValidation::make()->validators($this->params);
        $res = $this->service->edit($this->params);
        return $this->outputSuccess($res);
    }
}