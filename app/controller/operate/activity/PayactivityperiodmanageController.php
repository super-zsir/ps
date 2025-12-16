<?php

namespace Imee\Controller\Operate\Activity;

use Imee\Comp\Common\Export\Service\ExportService;
use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Payactivity\PayActivityPeriodManageValidation;
use Imee\Export\Operate\Activity\PayActivityPeriodManageExport;
use Imee\Service\Operate\Payactivity\PayActivityPeriodManageService;

class PayactivityperiodmanageController extends BaseController
{
    /**
     * @var PayActivityPeriodManageService $service
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new PayActivityPeriodManageService();
    }

    /**
     * @page payactivityperiodmanage
     * @name 累充
     */
    public function mainAction()
    {
    }

    /**
     * @page payactivityperiodmanage
     * @point 列表
     */
    public function listAction()
    {
        $c = $this->params['c'] ?? '';
        if ($c == 'options') {
            return $this->outputSuccess($this->service->getOptions());
        }
        $list = $this->service->getList($this->params);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }

    /**
     * @page payactivityperiodmanage
     * @point 创建
     * @logRecord(content = '创建', action = '0', model = 'payactivityperiodmanage', model_id = 'id')
     */
    public function createAction()
    {
        PayActivityPeriodManageValidation::make()->validators($this->params);
        $data = $this->service->create($this->params);
        return $this->outputSuccess($data);
    }

    /**
     * @page payactivityperiodmanage
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'payactivityperiodmanage', model_id = 'id')
     */
    public function modifyAction()
    {
        if (!isset($this->params['id']) || empty($this->params['id'])) {
            return $this->outputError(-1, 'ID错误');
        }
        PayActivityPeriodManageValidation::make()->validators($this->params);
        $data = $this->service->modify($this->params);
        return $this->outputSuccess($data);
    }

    /**
     * @page payactivityperiodmanage
     * @point 复制
     * @logRecord(content = '复制', action = '0', model = 'payactivityperiodmanage', model_id = 'id')
     */
    public function copyAction()
    {
        $id = $this->params['id'] ?? 0;
        return $this->outputSuccess($this->service->copy($id));
    }

    /**
     * @page payactivityperiodmanage
     * @point 发布
     * @logRecord(content = '发布', action = '1', model = 'payactivityperiodmanage', model_id = 'id')
     */
    public function publishAction()
    {
        $c = $this->params['c'] ?? '';
        if ($c == 'check') {
            return $this->outputSuccess($this->service->check($this->params));
        }
        return $this->outputSuccess($this->service->publish($this->params));
    }

    /**
     * @page payactivityperiodmanage
     * @point OA发布
     * @logRecord(content = 'OA发布', action = '1', model = 'payactivityperiodmanage', model_id = 'id')
     */
    public function oapubAction()
    {
        $this->service->pubAuthCheck($this->params);
        return $this->outputSuccess($this->service->publish($this->params, true));
    }

    /**
     * @page payactivityperiodmanage
     * @point 详情
     */
    public function infoAction()
    {
        $id = $this->params['id'] ?? 0;
        return $this->outputSuccess($this->service->info($id));
    }

    /**
     * @page payactivityperiodmanage
     * @point 删除
     * @logRecord(content = '删除', action = '2', model = 'payactivityperiodmanage', model_id = 'id')
     */
    public function deleteAction()
    {
        $id = $this->params['id'] ?? 0;
        $c = $this->params['c'] ?? '';
        if ($c == 'check') {
            $info = $this->service->info($id);
            return $this->outputSuccess([
                'is_confirm'   => 1,
                'confirm_text' => "你确定要删除【{$info['admin']}】创建的【{$info['title']}】吗？删除后将不可恢复"
            ]);
        }
        return $this->outputSuccess($this->service->delete($id));
    }

    /**
     * @page payactivityperiodmanage
     * @point 导出
     */
    public function exportAction()
    {
        $params = $this->service->checkExport($this->params);
        ExportService::addTask($this->uid, 'payactivityperiodmanage.xlsx', [PayActivityPeriodManageExport::class, 'export'], $params, '累充活动数据导出');
        ExportService::showHtml();
        return $this->outputSuccess();
    }
}