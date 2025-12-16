<?php

namespace Imee\Controller\Operate\Livevideo;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Livevideo\RoomBottomConfigValidation;
use Imee\Service\Operate\Livevideo\RoomBottomConfigService;

class RoombottomconfigController extends BaseController
{
    /**
     * @var RoomBottomConfigService $service
     */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new RoomBottomConfigService();
    }

    /**
     * @page roombottomconfig
     * @name 视频直播置底
     */
    public function mainAction()
    {
    }

    /**
     * @page  roombottomconfig
     * @point 列表
     */
    public function listAction()
    {
        $params = $this->params;
        $c = trim($params['c'] ?? '');

        switch ($c) {
            case 'log':
                $data = $this->service->getLogListAndTotal($this->params);
                return $this->outputSuccess($data['data'], ['total' => $data['total']]);
            default:
                $data = $this->service->getListAndTotal($this->params);
                return $this->outputSuccess($data['data'], ['total' => $data['total']]);
        }
    }

    /**
     * @page  roombottomconfig
     * @point 置底时间扣除
     */
    public function configAction()
    {
        RoomBottomConfigValidation::make()->validators($this->params);
        list($flg, $rec) = $this->service->config($this->params);
        return $flg ? $this->outputSuccess($rec) : $this->outputError(-1, $rec);
    }

}