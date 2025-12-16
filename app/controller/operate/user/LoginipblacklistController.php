<?php

namespace Imee\Controller\Operate\User;

use Imee\Comp\Common\Export\Service\ExportService;
use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\User\LoginIpBlackListValidation;
use Imee\Export\Operate\User\LoginIpBlackListExport;
use Imee\Models\Xsst\BmsOperateLog;
use Imee\Service\Operate\User\LoginIpBlackListService;

class LoginipblacklistController extends BaseController
{
    /**
     * @var LoginIpBlackListService
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new LoginIpBlackListService();
    }

    /**
     * @page loginipblacklist
     * @name 登录设备IP黑名单
     */
    public function mainAction()
    {
    }

    /**
     * @page loginipblacklist
     * @point 列表
     */
    public function listAction()
    {
        $list = $this->service->getList($this->params);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }

    /**
     * @page loginipblacklist
     * @point 创建
     * @logRecord(content = '创建', action = '0', model = 'loginipblacklist', model_id = 'id')
     */
    public function createAction()
    {
        $params = $this->trimParams($this->params);
        LoginIpBlackListValidation::make()->validators($params);
        $id = $this->service->create($params);
        return $this->outputSuccess(['type' => BmsOperateLog::TYPE_OPERATE_LOG, 'after_json' => $this->params, 'id' => $id]);
    }

    /**
     * @page loginipblacklist
     * @point 删除
     * @logRecord(content = '删除', action = '2', model = 'loginipblacklist', model_id = 'id')
     */
    public function deleteAction()
    {
        $id = $this->params['id'] ?? 0;
        if (empty($id)) {
            return $this->outputError(-1, 'ID错误');
        }
        $this->service->delete($id);
        return $this->outputSuccess(['after_json' => $this->params]);
    }

    /**
     * @page loginipblacklistx
     * @point 导出
     */
    public function exportAction()
    {
        $this->params['guid'] = 'loginipblacklist';
        ExportService::addTask($this->uid, 'loginipblacklist.xlsx', [LoginIpBlackListExport::class, 'export'], $this->params, '登录设备IP黑名单导出');
        ExportService::showHtml();

        return $this->outputSuccess();
    }
}