<?php

namespace Imee\Controller\Operate;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Roombackground\BackgroundCreateValidation;
use Imee\Controller\Validation\Roombackground\BackgroundModifyValidation;
use Imee\Service\Operate\Roombackground\BackgroundService;

class RoombackgroundController extends BaseController
{
    /**
     * @var BackgroundService $service
     */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new BackgroundService();
    }

    /**
     * @page roombackground
     * @name 素材管理
     */
    public function mainAction()
    {
    }

    /**
     * @page roombackground
     * @point 列表
     */
    public function listAction()
    {
        $list = $this->service->getList($this->params);
        return $this->outputSuccess($list['data'], ['total' => $list['total']]);
    }

    /**
     * @page roombackground
     * @point 创建
     */
    public function createAction()
    {
        BackgroundCreateValidation::make()->validators($this->params);
        list($res, $msg) = $this->service->create($this->params);
        if (!$res) {
            return $this->outputError(-1, $msg);
        }
        return $this->outputSuccess($msg);
    }

    /**
     * @page roombackground
     * @point 修改
     */
    public function modifyAction()
    {
        BackgroundModifyValidation::make()->validators($this->params);
        list($res, $msg) = $this->service->modify($this->params);
        if (!$res) {
            return $this->outputError(-1, $msg);
        }
        return $this->outputSuccess($msg);
    }

    /**
     * @page roombackground
     * @point 删除
     */
    public function deleteAction()
    {
        if (!isset($this->params['mid']) || empty($this->params['mid'])) {
            return $this->outputError(-1, 'Material ID错误');
        }
        list($res, $msg) = $this->service->delete($this->params['mid']);
        if (!$res) {
            return $this->outputError(-1, $msg);
        }
        return $this->outputSuccess($msg);
    }
}