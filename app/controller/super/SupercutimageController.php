<?php

namespace Imee\Controller\Super;

use Imee\Controller\BaseController;
use Imee\Service\Super\SuperService;

/**
 * 操作日志
 */
class SupercutimageController extends BaseController
{
    /**
     * @page supercutimage
     * @name 超管系统-视频直播截帧
     */
    public function mainAction()
    {
    }

    /**
     * @page  supercutimage
     * @point 列表
     */
    public function listAction()
    {
        $data = SuperService::getInstance()->imageList($this->params);
        return $this->outputSuccess($data["data"], ["total" => $data["total"]]);
    }
}