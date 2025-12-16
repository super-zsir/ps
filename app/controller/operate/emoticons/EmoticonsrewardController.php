<?php

namespace Imee\Controller\Operate\Emoticons;

use Imee\Comp\Common\Fixed\Csv;
use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Emoticons\Reward\AddValidation;
use Imee\Helper\Traits\ImportTrait;
use Imee\Models\Xs\XsEmoticonsReward;
use Imee\Service\Operate\Emoticons\EmoticonsRewardService;

class EmoticonsrewardController extends BaseController
{
    use ImportTrait;

    /**
     * @var EmoticonsRewardService $service
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new EmoticonsRewardService();
    }
    
    /**
     * @page emoticonsreward
     * @name 表情包下发
     */
    public function mainAction()
    {
    }
    
    /**
     * @page emoticonsreward
     * @point 列表
     */
    public function listAction()
    {
        $this->params['reward_type'] = 1; //1 下发  2 回收
        $data = $this->service->getList($this->params);
        return $this->outputSuccess($data['data'] ?? [], ['total' => $data['total'] ?? 0]);
    }
    
    /**
     * @page emoticonsreward
     * @point 下发
     * @logRecord(content = '下发', action = '0', model = 'emoticonsreward', model_id = 'id')
     */
    public function createAction()
    {
        AddValidation::make()->validators($this->params);
        $data = $this->service->create($this->params);
        return $this->outputSuccess($data);
    }
    
    /**
     * @page emoticonsreward
     * @point 批量下发
     * @logRecord(content = '批量下发', action = '0', model = 'emoticonsreward', model_id = 'id')
     */
    public function importAction()
    {
        if (($this->params['c'] ?? '') == 'tpl') {
            (new Csv())->exportToCsv(array_values(XsEmoticonsReward::$uploadFields), [], 'emoticons.reward');
            exit;
        }
        [$result, $msg, $data] = $this->uploadCsv(array_keys(XsEmoticonsReward::$uploadFields));
        if (!$result || empty($data['data'])) {
            return $this->outputError(-1, $msg ?: '请上传数据');
        }

        $data = $this->service->import($data['data']);
        return $this->outputSuccess($data);
    }
}