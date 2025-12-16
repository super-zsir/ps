<?php

namespace Imee\Controller\Operate\Relieveforbiddencard;

use Imee\Comp\Common\Fixed\Csv;
use Imee\Controller\BaseController;
use Imee\Helper\Traits\ImportTrait;
use Imee\Service\Operate\Relieveforbiddencard\RelieveForbiddenCardSendService;

class RelieveforbiddencardsendController extends BaseController
{
    use ImportTrait;

    /**
     * @var RelieveForbiddenCardSendService $service
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new RelieveForbiddenCardSendService();
    }

    /**
     * @page relieveforbiddencardsend
     * @name 解封卡发放
     */
    public function mainAction()
    {
    }

    /**
     * @page relieveforbiddencardsend
     * @point 列表
     */
    public function listAction()
    {
        $list = $this->service->getList($this->params);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }

    /**
     * @page relieveforbiddencardsend
     * @point 创建
     * @logRecord(content = '创建', action = '0', model = 'relieveforbiddencardsend', model_id = 'id')
     */
    public function createAction()
    {
        $data = $this->service->create($this->params);
        return $this->outputSuccess($data);
    }

    /**
     * @page relieveforbiddencardsend
     * @point 批量发放
     */
    public function addBatchAction()
    {
        $c = $this->params['c'] ?? '';
        if ($c == 'tpl') {
            (new Csv())->exportToCsv(['UID', '下发商城ID', '下发数量', '下发有效时间', '备注', '上传时需删除表头'], [], 'relieveForbiddenCardSend');
            exit;
        }
        [$success, $msg, $data] = $this->uploadCsv(['uid', 'prop_card_id', 'num', 'expired_time', 'remark']);
        if (!$success) {
            return $this->outputError('-1', $msg);
        }
        $data = $this->service->addBatch($data['data']);
        return $this->outputSuccess($data);
    }


}