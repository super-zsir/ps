<?php

namespace Imee\Controller\Operate;

use Imee\Comp\Common\Fixed\Csv;
use Imee\Controller\BaseController;
use Imee\Controller\Validation\Roombackground\BackgroundSendValidation;
use Imee\Helper\Traits\ImportTrait;
use Imee\Service\Operate\Roombackground\BackgroundSendService;

class BackgroundsendController extends BaseController
{
    use ImportTrait;

    /**
     * @var BackgroundSendService $service
     */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new BackgroundSendService();
    }

    /**
     * @page backgroundsend
     * @name 运营系统-房间背景管理-背景发放
     */
    public function mainAction()
    {
    }

    /**
     * @page backgroundsend
     * @point 列表
     */
    public function listAction()
    {
        $list = $this->service->getList($this->params);
        return $this->outputSuccess($list['data'], ['total' => $list['total']]);
    }

    /**
     * @page backgroundsend
     * @point 批量发放
     */
    public function sendBatchAction()
    {
        list($result, $msg, $data) = $this->uploadCsv(['uid', 'bg_id', 'duration', 'source']);
        if (!$result) {
            return $this->outputError(-1, $msg);
        }

        $uids = array_column($data['data'], 'uid');

        list($vRes, $ids) = $this->service->validationUid($uids);

        if (empty($vRes)) {
            return $this->outputError(-1, $ids);
        }

        $files = $this->request->getUploadedFiles();
        $file = $files[0];

        $ossUrl = $this->service->uploadOss($file);

        if (!$ossUrl) {
            return $this->outputError(-1, 'oss上传失败');
        }
        $this->params['file'] = $ossUrl;
        $this->params['items'] = $data['data'];

        list($res, $msg) = $this->service->sendBatch($this->params);

        if (!$res) {
            return $this->outputError(-1, $msg);
        }
        return $this->outputSuccess($msg);
    }

    /**
     * @page backgroundsend
     * @point 发放
     */
    public function sendAction()
    {
        BackgroundSendValidation::make()->validators($this->params);
        list($res, $msg) = $this->service->send($this->params);
        if (!$res) {
            return $this->outputError(-1, $msg);
        }
        return $this->outputSuccess($msg);
    }

    /**
     * @page backgroundsend
     * @point 批量发放模版下载
     */
    public function templateAction()
    {
        (new Csv())->exportToCsv(['UID', 'Background ID', 'Duration', '发放来源', '上传时需删除表头'], [], 'backgroundSend');
    }
}