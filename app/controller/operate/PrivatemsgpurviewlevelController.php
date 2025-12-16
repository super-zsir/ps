<?php

namespace Imee\Controller\Operate;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\PrivateMsgPurviewLevelValidation;
use Imee\Service\Operate\PrivateMsgPurviewLevelService;

class PrivatemsgpurviewlevelController extends BaseController
{
    /**
     * @var PrivateMsgPurviewLevelService
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new PrivateMsgPurviewLevelService();
    }

    /**
     * @page privatemsgpurviewlevel
     * @name 私信权限等级管理
     */
    public function mainAction()
    {
    }

    /**
     * @page privatemsgpurviewlevel
     * @point 列表
     */
    public function listAction()
    {
        $list = $this->service->getList($this->params);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }

    /**
     * @page privatemsgpurviewlevel
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'privatemsgpurviewlevel', model_id = 'id')
     */
    public function modifyAction()
    {
        PrivateMsgPurviewLevelValidation::make()->validators($this->params);
        $this->service->modify($this->params);
        return $this->outputSuccess(['after_json' => $this->params]);
    }
}