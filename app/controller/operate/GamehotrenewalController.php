<?php

namespace Imee\Controller\Operate;

use Imee\Controller\Validation\Operate\Gamehotrenewal\AddValidation;
use Imee\Service\Game\GamehotrenewalService;
use Imee\Controller\BaseController;

/**
 * 房间游戏热更新
 */
class GamehotrenewalController extends BaseController
{
    /** @var GamehotrenewalService $service */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new GamehotrenewalService();
    }

    /**
     * @page gamehotrenewal
     * @name 房间游戏热更新
     * @point 列表
     */
    public function listAction()
    {
        $result = $this->service->getListAndTotal(
            $this->params, 'id desc', $this->params['page'], $this->params['limit']
        );
        return $this->outputSuccess($result);
    }

    /**
     * @page gamehotrenewal
     * @point 删除
     */
    public function deleteAction()
    {
        if (empty($this->params['id'])) {
            return $this->outputError(-1, 'id不能为空！');
        }
        $result = $this->service->deleteById($this->params['id']);
        return $this->outputSuccess($result);
    }

    /**
     * @page gamehotrenewal
     * @point 上传
     */
    public function addAction()
    {
        AddValidation::make()->validators($this->params);
        list($result, $data) = $this->service->add($this->params);
        if (!$result) {
            return $this->outputError(-1, $data);
        }
        return $this->outputSuccess($data);
    }

    /**
     * @page gamehotrenewal
     * @point 编辑
     */
    public function editAction()
    {
        AddValidation::make()->validators($this->params);
        if (empty($this->params['id'])) {
            return $this->outputError(-1, 'id缺失');
        }
        list($result, $data) = $this->service->edit($this->params['id'], $this->params);
        if (!$result) {
            return $this->outputError(-1, $data);
        }
        return $this->outputSuccess($data);
    }

    /**
     * @page gamehotrenewal
     * @point 状态
     */
    public function statusAction()
    {
        if (empty($this->params['id'])) {
            return $this->outputError(-1, 'id缺失');
        }
        if (!isset($this->params['status'])) {
            return $this->outputError(-1, 'status缺失');
        }
        list($result, $data) = $this->service->status($this->params['id'], $this->params);
        if (!$result) {
            return $this->outputError(-1, $data);
        }
        return $this->outputSuccess($data);
    }
}