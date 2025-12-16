<?php

namespace Imee\Controller\Operate\Activity;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Activity\ActNavigationBarValidation;
use Imee\Service\Operate\Activity\ActNavigationBarService;

class ActnavigationbarController extends BaseController
{
    /**
     * @var ActNavigationBarService $service
     */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new ActNavigationBarService();
    }

    /**
     * @page actnavigationbar
     * @name 导航栏模板
     */
    public function mainAction()
    {
    }

    /**
     * @page  actnavigationbar
     * @point 列表
     */
    public function listAction()
    {
        $params = $this->params;
        $c = trim($params['c'] ?? '');

        switch ($c) {
            case 'options':
                return $this->outputSuccess($this->service->getOptions());
            case 'info':
                $id = intval($params['id'] ?? 0);
                return $this->outputSuccess($this->service->getInfo($id));
            default:
                $data = $this->service->getListAndTotal($this->params);
                return $this->outputSuccess($data['data'], ['total' => $data['total']]);
        }
    }

    /**
     * @page  actnavigationbar
     * @point 创建
     * @logRecord(content = '创建', action = '0', model = 'actnavigationbar', model_id = 'id')
     */
    public function createAction()
    {
        ActNavigationBarValidation::make()->validators($this->params);
        list($flg, $rec) = $this->service->add($this->params);
        return $flg ? $this->outputSuccess($rec) : $this->outputError(-1, $rec);
    }

    /**
     * @page  actnavigationbar
     * @point 复制
     * @logRecord(content = '复制', action = '1', model = 'actnavigationbar', model_id = 'id')
     */
    public function copyAction()
    {
        list($flg, $rec) = $this->service->copy($this->params);
        return $flg ? $this->outputSuccess($rec) : $this->outputError(-1, $rec);
    }

    /**
     * @page  actnavigationbar
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'actnavigationbar', model_id = 'id')
     */
    public function modifyAction()
    {
        ActNavigationBarValidation::make()->validators($this->params);
        list($flg, $rec) = $this->service->modify($this->params);
        return $flg ? $this->outputSuccess($rec) : $this->outputError(-1, $rec);
    }

    /**
     * @page  actnavigationbar
     * @point 删除
     * @logRecord(content = '删除', action = '2', model = 'actnavigationbar', model_id = 'id')
     */
    public function deleteAction()
    {
        list($flg, $rec) = $this->service->delete($this->params);
        return $flg ? $this->outputSuccess($rec) : $this->outputError(-1, $rec);
    }

    /**
     * @page  actnavigationbar
     * @point 导出
     */
    public function exportAction()
    {
    }
}