<?php

namespace Imee\Controller\Forbidden;

use Imee\Controller\BaseController;
use Imee\Service\Forbidden\DeviceForbiddenLogService;

class DeviceforbiddenlogController extends BaseController
{
    public function onConstruct()
    {
        parent::onConstruct();
    }

    /**
     * @page deviceforbiddenlog
     * @name 用户设备封禁记录
     */
    public function mainAction()
    {
    }

    /**
     * @page deviceforbiddenlog
     * @point 列表
     */
    public function listAction()
    {
        $service = new DeviceForbiddenLogService();
        $res = $service->list($this->params);
        return $this->outputSuccess($res['data'], ['total' => $res['total']]);
    }

    /**
     * @page deviceforbiddenlog
     * @point 创建
     * @logRecord(content = '创建', action = '0', model = 'deviceforbiddenlog', model_id = 'id')
     */
    public function createAction()
    {
    }

    /**
     * @page deviceforbiddenlog
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'deviceforbiddenlog', model_id = 'id')
     */
    public function modifyAction()
    {
    }

    /**
     * @page deviceforbiddenlog
     * @point 删除
     * @logRecord(content = '删除', action = '2', model = 'deviceforbiddenlog', model_id = 'id')
     */
    public function deleteAction()
    {
    }

    /**
     * @page deviceforbiddenlog
     * @point 导出
     */
    public function exportAction()
    {
    }
}