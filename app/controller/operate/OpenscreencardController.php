<?php

namespace Imee\Controller\Operate;

use Imee\Comp\Common\Fixed\Csv;
use Imee\Controller\BaseController;
use Imee\Helper\Traits\ImportTrait;
use Imee\Service\Operate\User\OpenScreenCardService;
use Imee\Controller\Validation\Operate\User\OpenScreenCardSendValidation;

class OpenscreencardController extends BaseController
{
    use ImportTrait;
    /**
     * @var OpenScreenCardService
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new OpenScreenCardService();
    }

    /**
     * @page openscreencard
     * @name 开屏卡发放管理
     */
    public function mainAction()
    {
    }

    /**
     * @page openscreencard
     * @point 列表
     */
    public function listAction()
    {
        $data = $this->service->getList($this->params);
        return $this->outputSuccess($data['data'] ?? [], ['total' => $data['total'] ?? 0]);
    }

    /**
     * @page openscreencard
     * @point 发放
     * @logRecord(content = '发放', action = '0', model = 'openscreencard', model_id = 'id')
     */
    public function sendAction()
    {
        OpenScreenCardSendValidation::make()->validators($this->params);
        $data = $this->service->send([$this->params]);
        return $this->outputSuccess($data);
    }

    /**
     * @page openscreencard
     * @point 批量发放
     * @logRecord(content = '修改', action = '1', model = 'openscreencard', model_id = 'id')
     */
    public function importAction()
    {
        $uploadFields = [
            'uid'            => 'uid',
            'num'            => '数量',
            'type'           => '卡片类型',
            'effective_hour' => '卡片持续有效期',
            'expired_time'   => '过期时间',
            //'can_send'       => '是否可赠送(请填数字：0-否 1-是)',
            'reason'         => '发放理由',
        ];
        if (($this->params['c'] ?? '') == 'tpl') {
            (new Csv())->exportToCsv(array_values($uploadFields), [], 'openscreencard');
            exit;
        }

        [$success, $msg, $data] = $this->uploadCsv(array_keys($uploadFields));
        if (!$success) {
            return $this->outputError('-1', $msg);
        }
        if (empty($data['data'])) {
            return $this->outputError('-1', '上传数据不能为空');
        }

        $canSend = $this->params['can_send'];
        if (!in_array($canSend, [0, 1])) {
            return $this->outputError('-1', '是否可赠送不正确');
        }

        $data['data'] = array_map(function($item) use ($canSend) {
            $item['can_send'] = (int)$canSend;
            return $item;
        }, $data['data']);

        $data = $this->service->send($data['data']);
        return $this->outputSuccess($data);
    }

    /**
     * @page openscreencard
     * @point 失效
     * @logRecord(content = '失效', action = '1', model = 'openscreencard', model_id = 'id')
     */
    public function expireAction()
    {
        $data = $this->service->expire($this->params);
        return $this->outputSuccess($data);
    }

    /**
     * @page openscreencard
     * @point 审核
     * @logRecord(content = '审核', action = '1', model = 'openscreencard', model_id = 'id')
     */
    public function auditAction()
    {
        $data = $this->service->audit($this->params);
        return $this->outputSuccess($data);
    }
}