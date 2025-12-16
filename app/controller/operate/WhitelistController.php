<?php

namespace Imee\Controller\Operate;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Whitelist\WhiteListValidation;
use Imee\Service\Operate\Whitelist\WhitelistService;

class WhitelistController extends BaseController
{
    /**
     * @var WhitelistService
     */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new WhitelistService();
    }

    /**
     * @page  whitelist
     * @name 运营系统-白名单管理
     */
    public function mainAction(){}

    /**
     * @page  whitelist
     * @point 列表
     */
    public function listAction()
    {
        $res = $this->service->getList($this->params, $this->params['page'] ?? 1, $this->params['limit'] ?? 15);
        return $this->outputSuccess($res['data'] ?? [], ['total' => $res['total'] ?? 0]);
    }

    /**
     * @page  whitelist
     * @point 创建
     */
    public function createAction()
    {
        WhiteListValidation::make()->validators($this->params);
        [$res, $msg] = $this->service->add($this->params);
        if (!$res) {
            return $this->outputError(-1, $msg);
        }
        return $this->outputSuccess([]);
    }

    /**
     * @page  whitelist
     * @point 编辑
     */
    public function modifyAction()
    {
        if (!isset($this->params['id']) || empty($this->params['id'])) {
            return $this->outputError(-1, 'ID必传');
        }
        WhiteListValidation::make()->validators($this->params);
        [$res, $msg] = $this->service->edit($this->params);
        if (!$res) {
            return $this->outputError(-1, $msg);
        }
        return $this->outputSuccess([]);
    }

    /**
     * @page  whitelist
     * @point 删除
     */
    public function deleteAction()
    {
        if (!isset($this->params['id']) || empty($this->params['id'])) {
            return $this->outputError(-1, 'ID必传');
        }
        [$res, $msg] = $this->service->delete($this->params['id'], $this->params['admin_uid']);
        if (!$res) {
            return $this->outputError(-1, $msg);
        }
        return $this->outputSuccess([]);
    }
}