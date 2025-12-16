<?php

namespace Imee\Controller\Operate\Relieveforbiddencard;

use Imee\Controller\BaseController;
use Imee\Service\Operate\Relieveforbiddencard\RelieveForbiddenCardSearchService;

class RelieveforbiddencardsearchController extends BaseController
{
    /**
     * @var RelieveForbiddenCardSearchService $service
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new RelieveForbiddenCardSearchService();
    }

    /**
     * @page relieveforbiddencardsearch
     * @name 解封卡背包查询
     */
    public function mainAction()
    {
    }

    /**
     * @page relieveforbiddencardsearch
     * @point 列表
     */
    public function listAction()
    {
        $list = $this->service->getList($this->params, $this->params['page'] ?? 1, $this->params['limit'] ?? 15);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }

    /**
     * @page relieveforbiddencardsearch
     * @point 回收
     * @logRecord(content = '回收', action = '1', model = 'relieveforbiddencardsearch', model_id = 'uid')
     */
    public function recoverAction()
    {
        $data = $this->service->recover($this->params);
        return $this->outputSuccess($data);
    }
}