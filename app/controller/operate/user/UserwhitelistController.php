<?php

namespace Imee\Controller\Operate\User;

use Imee\Comp\Common\Export\Service\ExportService;
use Imee\Comp\Common\Fixed\Csv;
use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Whitelist\UserWhiteListValidation;
use Imee\Export\Operate\User\UserWhitelistExport;
use Imee\Helper\Traits\ImportTrait;
use Imee\Service\Operate\Whitelist\UserWhiteListService;

class UserwhitelistController extends BaseController
{
    use ImportTrait;

    /**
     * @var UserWhiteListService
     */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new UserWhiteListService();
    }

    /**
     * @page  userwhitelist
     * @name 运营系统-用户管理-用户类白名单
     */
    public function mainAction()
    {
    }

    /**
     * @page  userwhitelist
     * @point 列表
     */
    public function listAction()
    {
        $res = $this->service->getList($this->params, $this->params['page'] ?? 1, $this->params['limit'] ?? 15);
        return $this->outputSuccess($res['data'] ?? [], ['total' => $res['total'] ?? 0]);
    }

    /**
     * @page  userwhitelist
     * @point 创建
     */
    public function createAction()
    {
        UserWhiteListValidation::make()->validators($this->params);
        [$res, $msg] = $this->service->add($this->params);
        if (!$res) {
            return $this->outputError(-1, $msg);
        }
        return $this->outputSuccess([]);
    }

    /**
     * @page  userwhitelist
     * @point 批量导入
     */
    public function importAction()
    {
        [$res, $msg, $data] = $this->uploadCsv(['type', 'uid']);
        if (!$res) {
            return $this->outputError(-1, $msg);
        }
        $uids = array_column($data['data'], 'uid');
        $uids = $this->service->handleIds($uids);

        if (empty($uids)) {
            return $this->outputError(-1, '上传uid数据为空');
        }

        [$result, $msg] = $this->service->importList($data['data']);
        if (!$result) {
            return $this->outputError(-1, $msg);
        }
        return $this->outputSuccess([]);
    }

    /**
     * @page userwhitelist
     * @point 批量添加模版下载
     */
    public function importTemplateAction()
    {
        (new Csv())->exportToCsv(['白名单类型','UID','上传前需删除表头'], [], 'import');
    }

    /**
     * @page  userwhitelist
     * @point 批量删除
     * @logRecord(content = "批量删除", action = "2", model = "XsstUidWhiteList", model_id = "id")
     */
    public function deleteBatchAction()
    {
        if (!isset($this->params['id']) || !is_array($this->params['id'])) {
            return $this->outputError(-1, 'ID错误');
        }
        [$res, $msg, $data] = $this->service->delete($this->params['id']);
        if (!$res) {
            return $this->outputError(-1, $msg);
        }
        return $this->outputSuccess($data);
    }

    /**
     * @page userwhitelist
     * @point 导出
     */
    public function exportAction()
    {
        $this->params['guid'] = 'userwhitelist';
        ExportService::addTask($this->uid, 'userwhitelist.xlsx', [UserWhitelistExport::class, 'export'], $this->params, '用户类白名单导出');
        ExportService::showHtml();

        return $this->outputSuccess();
    }
}