<?php

namespace Imee\Controller\Forbidden;

use Imee\Comp\Common\Export\Service\ExportService;
use Imee\Controller\BaseController;
use Imee\Export\Operate\User\UserWhitelistExport;
use Imee\Service\Forbidden\DeviceForbiddenService;
use Imee\Service\Forbidden\LoginInfoExport;
use Imee\Service\Forbidden\UserLoginInfoService;
use Imee\Service\Helper;

class PsuserdeviceinfoController extends BaseController
{
    public function onConstruct()
    {
        parent::onConstruct();
    }

    /**
     * @page psuserdeviceinfo
     * @name 用户登录设备信息查询
     */
    public function mainAction()
    {
    }

    /**
     * @page psuserdeviceinfo
     * @point 列表
     */
    public function listAction()
    {
        $service = new UserLoginInfoService();
        $res = $service->list($this->params);
        return $this->outputSuccess($res['data'], ['total' => $res['total']]);
    }

    /**
     * @page psuserdeviceinfo
     * @point 创建
     * @logRecord(content = '创建', action = '0', model = 'psuserdeviceinfo', model_id = 'id')
     */
    public function createAction()
    {
    }

    /**
     * @page psuserdeviceinfo
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'psuserdeviceinfo', model_id = 'id')
     */
    public function modifyAction()
    {
    }

    /**
     * @page psuserdeviceinfo
     * @point 删除
     * @logRecord(content = '删除', action = '2', model = 'psuserdeviceinfo', model_id = 'id')
     */
    public function deleteAction()
    {
    }

    /**
     * @page psuserdeviceinfo
     * @point 导出
     */
    public function exportAction()
    {
        $this->params['guid'] = 'psuserdeviceinfo';
        ExportService::addTask($this->uid, 'psuserdeviceinfo.xlsx', [LoginInfoExport::class, 'export'], $this->params, '用户登录信息导出');
//        ExportService::showHtml();
        return $this->outputSuccess();
    }

    /**
     * @page psuserdeviceinfo
     * @point 设备封禁
     */
    public function deviceforbiddenAction()
    {
        $service = new DeviceForbiddenService();
        $res = $service->forbidden($this->params);
        return $this->outputSuccess($res);
    }
}