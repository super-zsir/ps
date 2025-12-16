<?php

namespace Imee\Controller\Operate;

use Imee\Comp\Common\Fixed\Csv;
use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Medal\MedalIssuedValidation;
use Imee\Helper\Traits\ImportTrait;
use Imee\Service\Operate\Medal\MedalIssuedService;

class MedalissuedController extends BaseController
{
    use ImportTrait;

    /**
     * @var MedalIssuedService
     */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new MedalIssuedService();
    }

    /**
     * @page medalissued
     * @name 运营系统-勋章-勋章下发
     */
    public function mainAction()
    {
    }

    /**
     * @page  medalissued
     * @point 列表
     */
    public function listAction()
    {
        $res = $this->service->getListAndTotal($this->params);
        return $this->outputSuccess($res['data'] ?? [], ['total' => $res['total'] ?? 0]);
    }

    /**
     * @page medalissued
     * @point 创建
     */
    public function createAction()
    {
        MedalIssuedValidation::make()->validators($this->params);
        [$res, $msg] = $this->service->add($this->params);
        if (!$res) {
            return $this->outputError(-1, $msg);
        }
        return $this->outputSuccess([]);
    }

    /**
     * @page medalissued
     * @point 下载导入模版
     */
    public function importTemplateAction()
    {
        (new Csv())->exportToCsv(['用户ID', '勋章ID', '有效期（天）', '备注（第一行必填）', '发放来源（第一行必填）', '上传前请删除表头'], [], 'sendImport');
    }

    /**
     * @page medalissued
     * @point 批量发放
     */
    public function batchSendAction()
    {
        [$result, $msg, $data] = $this->uploadCsv(['uid', 'medal', 'expire_time', 'reason', 'source']);
        if (!$result) {
            return $this->outputError(-1, $msg);
        }
        if (count($data) > 500) {
            return $this->outputError('-1','发放数量最多为500个，请分批发放');
        }

        [$res, $msg] = $this->service->addBatch($data['data'], $this->params['admin_id']);
        if (!$res) {
            return $this->outputError(-1, $msg);
        }
        return $this->outputSuccess([]);
    }
}