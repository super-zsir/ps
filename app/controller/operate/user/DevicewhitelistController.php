<?php

namespace Imee\Controller\Operate\User;

use Imee\Comp\Common\Export\Service\ExportService;
use Imee\Comp\Common\Fixed\Csv;
use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\User\DeviceWhitelistBatchValidation;
use Imee\Controller\Validation\Operate\User\DeviceWhitelistValidation;
use Imee\Export\Operate\User\DeviceWhitelistExport;
use Imee\Helper\Traits\ImportTrait;
use Imee\Service\Operate\User\DeviceWhitelistService;

class DevicewhitelistController extends BaseController
{
    use ImportTrait;

    /**
     * @var DeviceWhitelistService
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new DeviceWhitelistService();
    }

    /**
     * @page devicewhitelist
     * @name 设备类白名单
     */
    public function mainAction()
    {
    }

    /**
     * @page devicewhitelist
     * @point 列表
     */
    public function listAction()
    {
        $list = $this->service->getList($this->params);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }

    /**
     * @page devicewhitelist
     * @point 创建
     * @logRecord(content = '创建', action = '0', model = 'devicewhitelist', model_id = 'id')
     */
    public function createAction()
    {
        DeviceWhitelistValidation::make()->validators($this->params);
        $id = $this->service->add($this->params);
        return $this->outputSuccess(['id' => $id, 'after_json' => $this->params]);
    }

    /**
     * @page devicewhitelist
     * @point 批量创建
     */
    public function addBatchAction()
    {
        $c = $this->params['c'] ?? '';
        if ($c == 'tpl') {
            (new Csv())->exportToCsv(['设备类型', '白名单类型ID', '设备mac', '备注'], [], 'deviceWhitelist');
            exit;
        }
        list($result, $msg, $data) = $this->uploadCsv(['device_type', 'whitelist_type', 'mac', 'remark']);
        if (!$result) {
            return $this->outputError(-1, $msg);
        }
        DeviceWhitelistBatchValidation::make()->validators(['data' => $data['data']]);
        $this->service->addBatch($data['data']);
        return $this->outputSuccess([]);
    }

    /**
     * @page devicewhitelist
     * @point 删除
     * @logRecord(content = '删除', action = '2', model = 'devicewhitelist', model_id = 'id')
     */
    public function deleteAction()
    {
        $id = $this->params['id'] ?? 0;
        if (empty($id)) {
            return $this->outputError(-1, '数据错误');
        }
        $this->service->deleteBatch([$id]);
        return $this->outputSuccess(['after_json' => []]);
    }

    /**
     * @page devicewhitelist
     * @point 批量删除
     * @logRecord(content = '删除', action = '2', model = 'devicewhitelist', model_id = 'id')
     */
    public function deleteBatchAction()
    {
        $ids = $this->params['id'] ?? [];
        if (empty($ids)) {
            return $this->outputError(-1, '数据错误');
        }
        $this->service->deleteBatch($ids);
        return $this->outputSuccess(['after_json' => []]);
    }

    /**
     * @page devicewhitelist
     * @point 导出
     */
    public function exportAction()
    {
        $this->params['guid'] = 'devicewhitelist';
        ExportService::addTask($this->uid, 'devicewhitelist.xlsx', [DeviceWhitelistExport::class, 'export'], $this->params, '设备类白名单导出');
        ExportService::showHtml();

        return $this->outputSuccess();
    }
}