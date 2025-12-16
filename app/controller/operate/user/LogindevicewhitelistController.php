<?php

namespace Imee\Controller\Operate\User;

use Imee\Comp\Common\Export\Service\ExportService;
use Imee\Comp\Common\Fixed\Csv;
use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Whitelist\LoginDeviceWhiteListValidation;
use Imee\Export\Operate\Whitelist\LoginDeviceWhiteListExport;
use Imee\Helper\Traits\ImportTrait;
use Imee\Service\Operate\Whitelist\LoginDeviceWhiteListService;

class LogindevicewhitelistController extends BaseController
{
    use ImportTrait;

    /**
     * @var LoginDeviceWhiteListService
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new LoginDeviceWhiteListService();
    }

    /**
     * @page logindevicewhitelist
     * @name 用户管理-登陆设备白名单
     */
    public function mainAction()
    {
    }

    /**
     * @page logindevicewhitelist
     * @point 列表
     */
    public function listAction()
    {
        $list = $this->service->getList($this->params);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }

    /**
     * @page logindevicewhitelist
     * @point 创建
     * @logRecord(content = '创建', action = '0', model = 'logindevicewhitelist', model_id = 'id')
     */
    public function createAction()
    {
        LoginDeviceWhiteListValidation::make()->validators($this->params);
        $id = $this->service->add($this->params);
        return $this->outputSuccess(['id' => $id, 'after_json' => $this->params]);

    }

    /**
     * @page logindevicewhitelist
     * @point 删除
     * @logRecord(content = '删除', action = '2', model = 'logindevicewhitelist', model_id = 'id')
     */
    public function deleteAction()
    {
        $id = $this->params['id'] ?? 0;
        if (empty($id)) {
            return $this->outputError(-1, 'id必填');
        }
        $res = $this->service->delete($id);
        return $this->outputSuccess($res);
    }

    /**
     * @page logindevicewhitelist
     * @point 导出
     */
    public function exportAction()
    {
        $this->params['guid'] = 'logindevicewhitelist';
        ExportService::addTask($this->uid, 'logindevicewhitelist.xlsx', [LoginDeviceWhiteListExport::class, 'export'], $this->params, '登陆设备白名单导出');
        ExportService::showHtml();

        return $this->outputSuccess();
    }

    /**
     * @page logindevicewhitelist
     * @point 批量添加
     * @logRecord(content = '批量添加', action = '0', model = 'logindevicewhitelist', model_id = 'id')
     */
    public function addBatchAction()
    {
        list($result, $msg, $data) = $this->uploadCsv(['object_id', 'comments']);
        if (!$result) {
            return $this->outputError(-1, $msg);
        }

        $this->params['data'] = $data['data'] ?? [];

        $id = $this->service->addBatch($this->params);

        return $this->outputSuccess(['id' => $id, 'after_json' => $data['data'] ?? []]);
    }

    /**
     * @page logindevicewhitelist
     * @point 批量删除
     * @logRecord(content = '批量删除', action = '2', model = 'logindevicewhitelist', model_id = 'id')
     */
    public function delBatchAction()
    {
        $ids = $this->params['id'] ?? [];
        if (empty($ids)) {
            return $this->outputError(-1, 'id必填');
        }
        $this->service->delBatch($ids);

        return $this->outputSuccess(['after_json' => $this->params]);
    }

    /**
     * @page logindevicewhitelist
     * @point 下载批量添加模版
     */
    public function templateAction()
    {
        (new Csv())->exportToCsv(['Mac', '备注', '上传时需删除表头'], [], 'whitelistAdd');
    }
}