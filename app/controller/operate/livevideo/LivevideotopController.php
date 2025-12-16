<?php

namespace Imee\Controller\Operate\Livevideo;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Livevideo\LiveVideoTopModifyValidation;
use Imee\Controller\Validation\Operate\Livevideo\LiveVideoTopValidation;
use Imee\Models\Xs\XsRoomTopConfig;
use Imee\Service\Operate\Livevideo\LiveVideoService;

class LivevideotopController extends BaseController
{
    /**
     * @var LiveVideoService $service
     */
    private $service;
    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new LiveVideoService(XsRoomTopConfig::PROPERTY_LIVE_VIDEO_TOP, XsRoomTopConfig::TYPE_TOP);
    }
    
    /**
     * @page livevideotop
     * @name 视频直播置顶
     */
    public function mainAction()
    {
    }
    
    /**
     * @page livevideotop
     * @point 列表
     */
    public function listAction()
    {
        $list = $this->service->getListAndTotal($this->params);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }
    
    /**
     * @page livevideotop
     * @point 置顶
     * @logRecord(content = '置顶', action = '1', model = 'livevideotop', model_id = 'id')
     */
    public function topAction()
    {
        LiveVideoTopValidation::make()->validators($this->params);
        $data = $this->service->topAndRemove($this->params);
        return $this->outputSuccess($data);
    }
    
    /**
     * @page livevideotop
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'livevideotop', model_id = 'id')
     */
    public function modifyAction()
    {
        LiveVideoTopModifyValidation::make()->validators($this->params);
        $data = $this->service->modify($this->params);
        return $this->outputSuccess($data);
    }
    
    /**
     * @page livevideotop
     * @point 取消
     * @logRecord(content = '取消', action = '1', model = 'livevideotop', model_id = 'id')
     */
    public function cancelAction()
    {
        $data = $this->service->cancel($this->params);
        return $this->outputSuccess($data);
    }
}