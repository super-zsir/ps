<?php

namespace Imee\Controller\Operate;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\LiveVideoPkRewardConfigValidation;
use Imee\Service\Operate\Play\LiveVideoPkRewardConfigService;

class LivevideopkrewardconfigController extends BaseController
{
    /**
     * @var LiveVideoPkRewardConfigService $service
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new LiveVideoPkRewardConfigService();
    }
    
    /**
     * @page livevideopkrewardconfig
     * @name 视频直播2v2pk奖励管理
     */
    public function mainAction()
    {
    }
    
    /**
     * @page livevideopkrewardconfig
     * @point 列表
     */
    public function listAction()
    {
        $c = $this->params['c'] ?? '';
        if ($c == 'options') {
            return $this->outputSuccess($this->service->getOptions());
        }
        $list = $this->service->getListAndTotal();
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }
    
    /**
     * @page livevideopkrewardconfig
     * @point pk奖励任务配置
     * @logRecord(content = 'pk奖励任务配置', action = '1', model = 'livevideopkrewardconfig', model_id = 'big_area_id')
     */
    public function configAction()
    {
        LiveVideoPkRewardConfigValidation::make()->validators($this->params);
        $data = $this->service->config($this->params);
        return $this->outputSuccess($data);
    }

    /**
     * @page livevideopkrewardconfig
     * @point pk奖励任务配置详情
     */
    public function infoAction()
    {
        return $this->outputSuccess($this->service->info($this->params));
    }
}