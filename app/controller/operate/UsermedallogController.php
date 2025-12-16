<?php

namespace Imee\Controller\Operate;

use Imee\Controller\BaseController;
use Imee\Service\Operate\Medal\UserMedalService;

class UsermedallogController extends BaseController
{
    /**
     * @var UserMedalService
     */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new UserMedalService();
    }

    /**
     * @page usermedallog
     * @name 运营系统-勋章-用户勋章管理-操作列表
     * @point 列表
     */
    public function listAction()
    {
        if (empty($this->params['uid']) || empty($this->params['medal_id'])) {
            return $this->outputError('-1', 'UID 勋章ID必传');
        }

        $res = $this->service->getUserMedalLogList($this->params, $this->params['page'] ?? 1, $this->params['limit'] ?? 15);
        return $this->outputSuccess($res['data'] ?? [], ['total' => $res['total'] ?? 0]);
    }
}