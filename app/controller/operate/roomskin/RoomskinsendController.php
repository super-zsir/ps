<?php

namespace Imee\Controller\Operate\Roomskin;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Roomskin\RoomSkinSendValidation;
use Imee\Helper\Traits\ImportTrait;
use Imee\Service\Operate\Roomskin\RoomSkinSendService;

class RoomskinsendController extends BaseController
{
    use ImportTrait;

    /**
     * @var RoomSkinSendService $service
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new RoomSkinSendService();
    }
    
    /**
	 * @page roomskinsend
	 * @name 房间皮肤下发
	 */
    public function mainAction()
    {
    }
    
    /**
	 * @page roomskinsend
	 * @point 列表
	 */
    public function listAction()
    {
        $list = $this->service->getList($this->params);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }

    /**
     * @page roomskinsend
     * @point 发放
     */
    public function sendAction()
    {
        RoomSkinSendValidation::make()->validators($this->params);
        $this->service->send($this->params);
        return $this->outputSuccess();
    }

    /**
     * @page roomskinsend
     * @point 批量发放
     */
    public function sendBatchAction()
    {
        [$result, $msg, $data] = $this->uploadCsv(['uid', 'skin_id', 'effective_time']);
        if (!$result) {
            return $this->outputError(-1, $msg);
        }
        $uids = array_column($data['data'], 'uid');
        [$_, $data] = $this->service->validation($uids, $data['data']);

        $files = $this->request->getUploadedFiles();
        $file = $files[0];

        $ossUrl = $this->service->uploadOss($file);

        if (!$ossUrl) {
            return $this->outputError(-1, 'oss上传失败');
        }

        $this->service->sendBatch($data, $ossUrl);

        return $this->outputSuccess($msg);
    }
}