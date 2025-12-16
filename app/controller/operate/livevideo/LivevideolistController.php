<?php

namespace Imee\Controller\Operate\Livevideo;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Livevideo\LiveVideoListForbiddenValidate;
use Imee\Controller\Validation\Operate\Livevideo\LiveVideoListStopValidate;
use Imee\Service\Operate\Livevideo\LiveVideoListService;

class LivevideolistController extends BaseController
{
    /**
     * @var LiveVideoListService $service
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new LiveVideoListService();
    }
    
    /**
     * @page livevideolist
     * @name 视频直播列表
     */
    public function mainAction()
    {
    }
    
    /**
     * @page livevideolist
     * @point 列表
     */
    public function listAction()
    {
        $c = $this->params['c'] ?? '';
        if ($c == 'options') {
            return $this->outputSuccess($this->service->getForbiddenOptions());
        } else if ($c == 'log') {
            $log = $this->service->getForbiddenLog($this->params);
            return $this->outputSuccess($log);
        }
        $list = $this->service->getListAndTotal($this->params);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }
    
    /**
     * @page livevideolist
     * @point 结束直播
     * @logRecord(content = '结束直播', action = '1', model = 'livevideolist', model_id = 'id')
     */
    public function stopAction()
    {
        LiveVideoListStopValidate::make()->validators($this->params);
        $data = $this->service->stop($this->params);
        return $this->outputSuccess($data);
    }

    /**
     * @page livevideolist
     * @point 封禁
     * @logRecord(content = '封禁', action = '1', model = 'livevideolist', model_id = 'id')
     */
    public function forbiddenAction()
    {
        LiveVideoListForbiddenValidate::make()->validators($this->params);
        $data = $this->service->forbidden($this->params);
        return $this->outputSuccess($data);
    }
}