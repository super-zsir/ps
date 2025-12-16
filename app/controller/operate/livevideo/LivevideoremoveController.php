<?php

namespace Imee\Controller\Operate\Livevideo;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Livevideo\LiveVideoRemoveModifyValidation;
use Imee\Controller\Validation\Operate\Livevideo\LiveVideoRemoveValidation;
use Imee\Models\Xs\XsRoomTopConfig;
use Imee\Service\Operate\Livevideo\LiveVideoService;

class LivevideoremoveController extends BaseController
{
    /**
     * @var LiveVideoService $service
     */
    private $service;
    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new LiveVideoService(XsRoomTopConfig::PROPERTY_LIVE_VIDEO_TOP, XsRoomTopConfig::TYPE_REMOVE);
    }
    
    /**
     * @page livevideoremove
     * @name 视频直播移除
     */
    public function mainAction()
    {
    }
    
    /**
     * @page livevideoremove
     * @point 列表
     */
    public function listAction()
    {
        $list = $this->service->getListAndTotal($this->params);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }
    
    /**
     * @page livevideoremove
     * @point 移除
     * @logRecord(content = '移除', action = '1', model = 'livevideoremove', model_id = 'id')
     */
    public function removeAction()
    {
        LiveVideoRemoveValidation::make()->validators($this->params);
        $data = $this->service->topAndRemove($this->params);
        return $this->outputSuccess($data);
    }
    
    /**
     * @page livevideoremove
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'livevideoremove', model_id = 'id')
     */
    public function modifyAction()
    {
        LiveVideoRemoveModifyValidation::make()->validators($this->params);
        $data = $this->service->modify($this->params);
        return $this->outputSuccess($data);
    }
    
    /**
     * @page livevideoremove
     * @point 取消
     * @logRecord(content = '取消', action = '1', model = 'livevideoremove', model_id = 'id')
     */
    public function cancelAction()
    {
        $data = $this->service->cancel($this->params);
        return $this->outputSuccess($data);
    }
}