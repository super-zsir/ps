<?php

namespace Imee\Controller\Operate\Emoticons;

use Imee\Comp\Common\Fixed\Csv;
use Imee\Comp\Common\Fixed\ImportTrait;
use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Emoticons\CustomizedEmoticonRewardValidation;
use Imee\Service\Operate\Emoticons\CustomizedEmoticonRewardService;
use Imee\Service\Operate\Emoticons\CustomizedEmoticonService;
use Imee\Service\StatusService;

class CustomizedemoticonrewardController extends BaseController
{
    use ImportTrait;

    /**
     * @var CustomizedEmoticonRewardService $service
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new CustomizedEmoticonRewardService();
    }
    
    /**
     * @page customizedemoticonreward
     * @name 发放表情给红心tag
     */
    public function mainAction()
    {
    }
    
    /**
     * @page customizedemoticonreward
     * @point 列表
     */
    public function listAction()
    {
        if (($this->params['c'] ?? '') == 'query_emoticon') {
            $id = $this->params['str'] ?? 0;
            if (!is_numeric($id) || $id < 1) {
                return $this->outputSuccess([]);
            }
            $data = (new CustomizedEmoticonService())->getList([
                'id' => (int)$id,
                'limit' => 1,
                'page' => 1
            ]);
            $emotionMap = array_column($data['data'], 'name_show', 'id');
            return $this->outputSuccess(StatusService::formatMap($emotionMap));
        }
        $data = $this->service->getList($this->params);
        return $this->outputSuccess($data['data'] ?? [], ['total' => $data['total'] ?? 0]);
    }
    
    /**
     * @page customizedemoticonreward
     * @point 发放
     * @logRecord(content = '发放表情', action = '0', model = 'customizedemoticonreward', model_id = 'id')
     */
    public function createAction()
    {
        // 参数验证
        CustomizedEmoticonRewardValidation::make()->validators($this->params);
        
        $data = $this->service->create($this->params);
        return $this->outputSuccess($data);
    }
    
    /**
     * @page customizedemoticonreward
     * @point 批量发放
     * @logRecord(content = '批量发放表情', action = '0', model = 'customizedemoticonreward', model_id = 'id')
     */
    public function batchcreateAction()
    {
        if (($this->params['c'] ?? '') == 'tpl') {
            $upload = ['uid', '表情id', '生效时间(天)', '发放理由'];
            (new Csv())->exportToCsv($upload, [], 'customizedEmoticonRewardBatchCreate');
            exit;
        }
        $upload = ['uid', 'customized_emoticon_id', 'valid_day', 'reason'];
        [$success, $msg, $data] = $this->uploadCsv($upload);

        if (!$success) {
            return $this->outputError('-1', $msg);
        }
        if (empty($data['data'])) {
            return $this->outputError('-1', '请填写数据后再上传！');
        }
        if (count($data['data']) > 200) {
            return $this->outputError('-1', '批量发放最多支持200条记录');
        }
        
        $result = $this->service->batchCreate($data['data']);
        return $this->outputSuccess($result);
    }
    
    /**
     * @page customizedemoticonreward
     * @point 失效此表情
     * @logRecord(content = '失效表情', action = '1', model = 'customizedemoticonreward', model_id = 'id')
     */
    public function deactivateAction()
    {
        $data = $this->service->deactivate($this->params);
        return $this->outputSuccess($data);
    }
    
    /**
     * @page customizedemoticonreward
     * @point 详情
     */
    public function infoAction()
    {
        $id = (int)($this->params['id'] ?? 0);
        if ($id <= 0) {
            return $this->outputError('ID参数无效');
        }
        $data = $this->service->info($id);
        return $this->outputSuccess($data);
    }

    /**
     * @page customizedemoticonreward
     * @point 获取表情列表
     */
    public function getEmoticonAction()
    {
        if (empty($this->params['str']) || !is_numeric($this->params['str'])) {
            return $this->outputError('请输入表情id');
        }
        $data = $this->service->getEmoticon((int)$this->params['str']);
        return $this->outputSuccess($data);
    }
} 
