<?php

namespace Imee\Controller\Operate\Topcard;

use Imee\Comp\Common\Fixed\Csv;
use Imee\Controller\BaseController;
use Imee\Helper\Traits\ImportTrait;
use Imee\Service\Operate\Topcard\RoomTopCardSendService;

class RoomtopcardsendController extends BaseController
{
    use ImportTrait;

    /**
     * @var RoomTopCardSendService $service
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new RoomTopCardSendService();
    }

    /**
     * @page roomtopcardsend
     * @name 置顶卡发放
     */
    public function mainAction()
    {
    }

    /**
     * @page roomtopcardsend
     * @point 列表
     */
    public function listAction()
    {
        $list = $this->service->getList($this->params);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }

    /**
     * @page roomtopcardsend
     * @point 创建
     * @logRecord(content = '创建', action = '0', model = 'roomtopcardsend', model_id = 'id')
     */
    public function createAction()
    {
        list($res, $msg) = $this->service->create($this->params);
        if (!$res) {
            return $this->outputError(-1, $msg);
        }
        return $this->outputSuccess($msg);
    }

    /**
     * @page roomtopcardsend
     * @point 批量发放
     */
    public function addBatchAction()
    {
        $c = $this->params['c'] ?? '';
        if ($c == 'tpl') {
            (new Csv())->exportToCsv(['用户UID', '置顶卡ID', '发放数量', '有效期', '备注', '上传时需删除表头'], [], 'topCardSend');
            exit;
        }
        [$success, $msg, $data] = $this->uploadCsv(['uid', 'room_top_card_id', 'num', 'expired_time', 'remark']);
        if (!$success) {
            return $this->outputError('-1', $msg);
        }
        list($res, $msg) = $this->service->addBatch($data['data']);
        if (!$res) {
            return $this->outputError(-1, $msg);
        }
        return $this->outputSuccess($msg);
    }


}