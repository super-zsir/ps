<?php

namespace Imee\Controller\Operate\Background;

use Imee\Comp\Common\Fixed\Csv;
use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Background\Custombackground\CustomBackgroundCardBatchSendValidation;
use Imee\Controller\Validation\Operate\Background\Custombackground\CustomBackgroundCardSendValidation;
use Imee\Helper\Traits\ImportTrait;
use Imee\Service\Operate\Background\Custombackground\CustomBgcCardSendService;

class CustombgccardsendController extends BaseController
{
    use ImportTrait;

    /**
     * @var CustomBgcCardSendService $service
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new CustomBgcCardSendService();
    }

    /**
     * @page custombgccardsend
     * @name 自定义背景卡下发
     */
    public function mainAction()
    {
    }

    /**
     * @page custombgccardsend
     * @point 列表
     */
    public function listAction()
    {
        $list = $this->service->getList($this->params);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }

    /**
     * @page custombgccardsend
     * @point 批量发放模版下发
     */
    public function templateAction()
    {
        (new Csv())->exportToCsv(['UID', '背景卡数量', '类型 0静态 1动态', '单张背景卡有效期', '发放理由', '上传时需删除表头'], [], 'batchSendTemplate');
    }

    /**
     * @page custombgccardsend
     * @point 发放
     * @logRecord(content = '发放', action = '1', model = 'custombgccardsend', model_id = 'id')
     */
    public function sendAction()
    {
        CustomBackgroundCardSendValidation::make()->validators($this->params);
        $this->service->send($this->params);
        return $this->outputSuccess($this->params);
    }

    /**
     * @page custombgccardsend
     * @point 批量发放
     * @logRecord(content = '批量发放', action = '1', model = 'custombgccardsend', model_id = 'id')
     */
    public function batchSendAction()
    {
        list($result, $msg, $data) = $this->uploadCsv(['uid', 'num', 'card_type', 'valid_term', 'reason']);

        if (!$result) {
            return $this->outputError(-1, $msg);
        }
        CustomBackgroundCardBatchSendValidation::make()->validators($data);

        $canTransfer = intval($this->params['can_transfer'] ?? 1);
        if (count($data['data']) > 500) {
            return $this->outputError(-1, '单次发放最多500条，请分批上传');
        }
        foreach ($data['data'] as &$item) {
            $item['can_transfer'] = $canTransfer;
        }
        $this->service->sendBatch($data, $this->params['admin_id']);

        return $this->outputSuccess($this->params);
    }
}