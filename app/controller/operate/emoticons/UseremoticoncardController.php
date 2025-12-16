<?php

namespace Imee\Controller\Operate\Emoticons;

use Imee\Comp\Common\Fixed\Csv;
use Imee\Comp\Common\Fixed\ImportTrait;
use Imee\Controller\BaseController;
use Imee\Service\Operate\Emoticons\UserEmoticonCardService;

class UseremoticoncardController extends BaseController
{
    use ImportTrait;

    /** @var UserEmoticonCardService */
    private $service;
    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new UserEmoticonCardService();
    }
    
    /**
     * @page useremoticoncard
     * @name 定制表情卡发放
     */
    public function mainAction()
    {
    }
    
    /**
     * @page useremoticoncard
     * @point 列表
     */
    public function listAction()
    {
        $data = $this->service->getList($this->params, intval($this->params['page'] ?? 1), intval($this->params['limit'] ?? 15));
        return $this->outputSuccess($data['data'] ?? [], ['total' => $data['total'] ?? 0]);
    }
    
    /**
     * @page useremoticoncard
     * @point 发放
     * @logRecord(content = '发放', action = '0', model = 'useremoticoncard', model_id = 'id')
     */
    public function sendAction()
    {
        $data = $this->service->send($this->params);
        return $this->outputSuccess($data);
    }
    
    /**
     * @page useremoticoncard
     * @point 失效卡片
     * @logRecord(content = '失效卡片', action = '1', model = 'useremoticoncard', model_id = 'id')
     */
    public function expireAction()
    {
        $data = $this->service->expire($this->params);
        return $this->outputSuccess($data);
    }
    
    /**
     * @page useremoticoncard
     * @point 审核通过
     * @logRecord(content = '审核通过', action = '1', model = 'useremoticoncard', model_id = 'id')
     */
    public function passAction()
    {
        $this->params['pass'] = 1;
        $data = $this->service->audit($this->params);
        return $this->outputSuccess($data);
    }

    /**
     * @page useremoticoncard
     * @point 审核拒绝
     * @logRecord(content = '审核拒绝', action = '1', model = 'useremoticoncard', model_id = 'id')
     */
    public function refuseAction()
    {
        $this->params['pass'] = 0;
        $data = $this->service->audit($this->params);
        return $this->outputSuccess($data);
    }
    
    /**
     * @page useremoticoncard
     * @point 批量发放
     * @logRecord(content = '批量发放', action = '0', model = 'useremoticoncard', model_id = 'id')
     */
    public function batchAction()
    {
        if (($this->params['c'] ?? '') == 'tpl') {
            $upload = ['uid', '数量', '单张生效时间(天)', '过期时间', '是否可赠送(0-否，1-是)', '发放理由'];
            (new Csv())->exportToCsv($upload, [], 'useremoticoncardBatchCreate');
            exit;
        }
        $upload = ['uid', 'num', 'effective_days', 'expired_time', 'can_send', 'reason'];
        [$success, $msg, $data] = $this->uploadCsv($upload);

        if (!$success) {
            return $this->outputError('-1', $msg);
        }
        if (empty($data['data'])) {
            return $this->outputError('-1', '请填写数据后再上传！');
        }
        if (count($data['data']) > 1000) {
            return $this->outputError('-1', '一次最多1000条，请分批执行');
        }
        $data = $this->service->import($data['data']);
        return $this->outputSuccess($data);
    }
}