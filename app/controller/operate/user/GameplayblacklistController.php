<?php

namespace Imee\Controller\Operate\User;

use Imee\Comp\Common\Export\Service\ExportService;
use Imee\Comp\Common\Fixed\Csv;
use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\User\Blacklist\GameplayBlacklistAddBatchValidation;
use Imee\Controller\Validation\Operate\User\Blacklist\GameplayBlacklistAddValidation;
use Imee\Controller\Validation\Operate\User\Blacklist\GameplayBlacklistEditValidation;
use Imee\Export\Operate\User\GameplayBlacklistExport;
use Imee\Helper\Traits\ImportTrait;
use Imee\Models\Xs\XsUidGameBlackList;
use Imee\Service\Operate\User\GameplayBlacklistService;

class GameplayblacklistController extends BaseController
{
    use ImportTrait;

    /**
     * @var GameplayBlacklistService $service
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new GameplayBlacklistService();
    }
    
    /**
     * @page gameplayblacklist
     * @name 玩法黑名单
     */
    public function mainAction()
    {
    }

    /**
     * @page gameplayblacklist
     * @point 列表
     */
    public function listAction()
    {
        if (($this->params['c'] ?? '') == 'check') {
            $check = $this->service->checkExists($this->params);
            if ($check) {
                $text = '';
                $text .= sprintf('<p>序号：%s</p>', $check['id']);
                $text .= sprintf('<p>用户UID：%s</p>', $this->params['uid']);
                $text .= sprintf('<p>用户昵称：%s</p>', $check['name']);
                $text .= sprintf('<p>黑名单名称：%s</p>', $check['type']);
                $text .= sprintf('<p>状态：%s</p>', $check['status']);
                $text .= sprintf('<p>如需编辑，请按序号搜索编辑记录</p>');

                return $this->outputSuccess(['is_info' => true, 'confirm_text' => $text, 'width' => 700]);
            }
            return $this->outputSuccess(['is_confirm' => false]);
        } elseif (($this->params['c'] ?? '') == 'check_batch') {
            list($result, $msg, $data) = $this->uploadCsv(['uid', 'type', 'time_type', 'start_time', 'end_time']);
            if (!$result) {
                return $this->outputError(-1, $msg);
            }
            $check = $this->service->checkBatch($data['data']);
            if ($check) {
                $text = '';
                $text .= sprintf('<p>序号：%s</p>', $check['id']);
                $text .= sprintf('<p>用户UID：%s</p>', $check['uid']);
                $text .= sprintf('<p>用户昵称：%s</p>', $check['name']);
                $text .= sprintf('<p>黑名单名称：%s</p>', $check['type']);
                $text .= sprintf('<p>状态：%s</p>', $check['status']);
                $text .= sprintf('<p>如需编辑，请按序号搜索编辑记录</p>');

                return $this->outputSuccess(['is_info' => true, 'confirm_text' => $text, 'width' => 700]);
            }
            return $this->outputSuccess(['is_confirm' => false]);
        }
        $list = $this->service->getList($this->params);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }

    /**
     * @page gameplayblacklist
     * @point 创建
     * @logRecord(content = '创建', action = '0', model = 'gameplayblacklist', model_id = 'id')
     */
    public function createAction()
    {
        GameplayBlacklistAddValidation::make()->validators($this->params);
        list($res, $data) = $this->service->create($this->params);
        if (!$res) {
            return $this->outputError(-1, $data);
        }
        return $this->outputSuccess($data);
    }

    /**
     * @page gameplayblacklist
     * @point 批量添加
     * @logRecord(content = '批量添加', action = '0', model = 'gameplayblacklist', model_id = 'id')
     */
    public function addBatchAction()
    {
        $c = $this->params['c'] ?? '';
        if ($c == 'tpl') {
            (new Csv())->exportToCsv(['UID', '黑名单名称', '黑名单时效', '生效时间', '结束时间'], [], 'blacklist');
            exit;
        }
        list($result, $msg, $data) = $this->uploadCsv(['uid', 'type', 'time_type', 'start_time', 'end_time']);
        if (!$result) {
            return $this->outputError(-1, $msg);
        }
        GameplayBlacklistAddBatchValidation::make()->validators($data['data']);
        list($res, $data) = $this->service->addBatch($data['data']);
        if (!$res) {
            return $this->outputError(-1, $data);
        }
        return $this->outputSuccess($data);
    }

    /**
     * @page gameplayblacklist
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'gameplayblacklist', model_id = 'id')
     */
    public function modifyAction()
    {
        GameplayBlacklistEditValidation::make()->validators($this->params);
        list($res, $data) = $this->service->modify($this->params);
        if (!$res) {
            return $this->outputError(-1, $data);
        }
        return $this->outputSuccess($data);
    }

    /**
     * @page gameplayblacklist
     * @point 删除
     * @logRecord(content = '删除', action = '2', model = 'gameplayblacklist', model_id = 'id')
     */
    public function deleteBatchAction()
    {
        list($res, $data) = $this->service->deleteBatch($this->params);
        if (!$res) {
            return $this->outputError(-1, $data);
        }
        return $this->outputSuccess($data);
    }

    /**
     * @page gameplayblacklist
     * @point 取消
     * @logRecord(content = '取消', action = '1', model = 'gameplayblacklist', model_id = 'id')
     */
    public function cancelAction()
    {
        $this->params['time_type'] = XsUidGameBlackList::CANCEL_TIME_TYPE;
        GameplayBlacklistEditValidation::make()->validators($this->params);
        list($res, $data) = $this->service->modify($this->params);
        if (!$res) {
            return $this->outputError(-1, $data);
        }
        return $this->outputSuccess($data);
    }

    /**
     * @page gameplayblacklist
     * @point 导出
     */
    public function exportAction()
    {
        $this->params['guid'] = 'gameplayblacklist';
        ExportService::addTask($this->uid, 'gameplayblacklist.xlsx', [GameplayBlacklistExport::class, 'export'], $this->params, '玩法黑名单导出');
        ExportService::showHtml();

        return $this->outputSuccess();
    }

    /**
     * @page gameplayblacklist
     * @point 审核
     * @logRecord(content = '审核', action = '1', model = 'gameplayblacklist', model_id = 'id')
     */
    public function auditAction()
    {
        $id = $this->params['id'] ?? 0;
        $status = $this->params['status'] ?? 0;
        if (!is_numeric($id) || $id < 1 || !in_array($status, array_keys(XsUidGameBlackList::$auditStatusMap))) {
            return $this->outputError(-1, 'error');
        }

        $data = $this->service->audit((int)$id, (int)$status);
        return $this->outputSuccess($data);
    }
}