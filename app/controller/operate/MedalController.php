<?php

namespace Imee\Controller\Operate;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Medal\MedalValidation;
use Imee\Service\Operate\Medal\MedalService;

class MedalController extends BaseController
{
    /**
     * @var MedalService
     */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new MedalService();
    }

    /**
     * @page medal
     * @name 运营系统-勋章-勋章配置
     */
    public function mainAction()
    {
    }

    /**
     * @page  medal
     * @point 列表
     */
    public function listAction()
    {
        $res = $this->service->getList($this->params, $this->params['page'] ?? 1, $this->params['limit'] ?? 15);
        return $this->outputSuccess($res['data'] ?? [], ['total' => $res['total'] ?? 0]);
    }

    /**
     * @page medal
     * @point 创建
     */
    public function createAction()
    {
        MedalValidation::make()->validators($this->params);
        [$res, $msg] = $this->service->add($this->params);
        if (!$res) {
            return $this->outputError('-1', $msg);
        }
        return $this->outputSuccess([]);
    }

    /**
     * @page medal
     * @point 编辑
     */
    public function modifyAction()
    {
        if (!isset($this->params['id']) || $this->params['id'] < 1) {
            return $this->outputError('-1', 'ID错误');
        }
        MedalValidation::make()->validators($this->params);
        [$res, $msg] = $this->service->edit($this->params);
        if (!$res) {
            return $this->outputError('-1', $msg);
        }
        return $this->outputSuccess([]);
    }

    /**
     * @page medal
     * @point 上架
     */
    public function putAction()
    {
        if (!isset($this->params['id']) || empty($this->params['id'])) {
            return $this->outputError('-1', 'ID必填');
        }
        [$res, $msg] = $this->service->put($this->params['id']);
        if (!$res) {
            return $this->outputError('-1', $msg);
        }
        return $this->outputSuccess([]);
    }

    /**
     * @page medal
     * @point 下架
     */
    public function lowerAction()
    {
        if (!isset($this->params['id']) || empty($this->params['id'])) {
            return $this->outputError('-1', 'ID必填');
        }
        [$res, $msg] = $this->service->lower($this->params['id']);
        if (!$res) {
            return $this->outputError('-1', $msg);
        }
        return $this->outputSuccess([]);
    }
}