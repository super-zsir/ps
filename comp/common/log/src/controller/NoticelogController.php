<?php

namespace Imee\Controller\Log;

use Imee\Comp\Common\Log\Service\NoticeService;
use Imee\Controller\BaseController;

class NoticelogController extends BaseController
{
    public function onConstruct()
    {
        parent::onConstruct();
    }
    
    /**
     * @page noticelog
     * @name 通知记录
     */
    public function mainAction()
    {
    }
    
    /**
     * @page noticelog
     * @point 列表
     */
    public function listAction()
    {
        $list = NoticeService::getLogList($this->params);
        return $this->outputSuccess($list['data'], ['total' => $list['total']]);
    }
}