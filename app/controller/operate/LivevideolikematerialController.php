<?php

namespace Imee\Controller\Operate;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Likematerial\LiveVideoLikeMaterialAddValidation;
use Imee\Controller\Validation\Operate\Likematerial\LiveVideoLikeMaterialEditValidation;
use Imee\Service\Operate\LiveVideoLikeMaterialService;

class LivevideolikematerialController extends BaseController
{
    /** @var  LiveVideoLikeMaterialService $service */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new LiveVideoLikeMaterialService();
    }

    /**
     * @page livevideolikematerial
     * @name 视频直播点赞素材管理
     */
    public function mainAction()
    {
    }

    /**
     * @page livevideolikematerial
     * @point 列表
     */
    public function listAction()
    {
        $list = $this->service->getList($this->params);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }

    /**
     * @page livevideolikematerial
     * @point 创建
     * @logRecord(content = '创建', action = '0', model = 'livevideolikematerial', model_id = 'id')
     */
    public function createAction()
    {
        LiveVideoLikeMaterialAddValidation::make()->validators($this->params);
        $data = $this->service->create($this->params);
        return $this->outputSuccess($data);
    }

    /**
     * @page livevideolikematerial
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'livevideolikematerial', model_id = 'id')
     */
    public function modifyAction()
    {
        LiveVideoLikeMaterialEditValidation::make()->validators($this->params);
        $data = $this->service->modify($this->params);
        return $this->outputSuccess($data);
    }

    /**
     * @page livevideolikematerial
     * @point 立即失效
     * @logRecord(content = '立即失效', action = '1', model = 'livevideolikematerial', model_id = 'id')
     */
    public function failureAction()
    {
        $id = $this->params['id'] ?? 0;
        if (empty($id)) {
            return $this->outputError(-1, 'ID错误');
        }
        $data = $this->service->failure($id);
        return $this->outputSuccess($data);
    }

    /**
     * @page livevideolikematerial
     * @point 详情
     */
    public function infoAction()
    {
        $id = $this->params['id'] ?? 0;
        if (empty($id)) {
            return $this->outputError(-1, 'ID错误');
        }
        return $this->outputSuccess($this->service->info($id));
    }
}