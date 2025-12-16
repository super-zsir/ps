<?php

namespace Imee\Controller\Operate\Gift;

use Imee\Controller\BaseController;
use Imee\Service\Operate\Gift\GiftService;

class GiftlogController extends BaseController
{
    public function onConstruct()
    {
        parent::onConstruct();
    }
    
    /**
     * @page giftlog
     * @name 礼物操作日志
     */
    public function mainAction()
    {
    }
    
    /**
     * @page giftlog
     * @point 列表
     */
    public function listAction()
    {
        $id = $this->params['id'] ?? 0;
        if ($id < 1) {
            return $this->outputSuccess();
        }

        $data = (new GiftService())->getLogList((int)$id, $this->params['page'] ?? 1, $this->params['limit'] ?? 15);
        return $this->outputSuccess($data['data'] ?? [], ['total' => $data['total'] ?? 0]);
    }
}