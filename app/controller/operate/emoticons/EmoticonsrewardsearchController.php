<?php

namespace Imee\Controller\Operate\Emoticons;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Emoticons\Reward\AddValidation;
use Imee\Controller\Validation\Operate\Emoticons\Reward\ReduceValidation;
use Imee\Helper\Traits\ImportTrait;
use Imee\Service\Operate\Emoticons\EmoticonsRewardService;

class EmoticonsrewardsearchController extends BaseController
{

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
     * @page emoticonsrewardsearch
     * @name 表情包下发搜索
     */
    public function mainAction()
    {
    }

    /**
     * @page emoticonsrewardsearch
     * @point 列表
     */
    public function listAction()
    {
        $data = $this->service->getSearchList($this->params);
        return $this->outputSuccess($data['data'] ?? [], ['total' => $data['total'] ?? 0]);
    }

    /**
     * @page emoticonsrewardsearch
     * @point 回收
     * @logRecord(content = '回收', action = '1', model = 'emoticonsrewardsearch', model_id = 'id')
     */
    public function reduceAction()
    {
        ReduceValidation::make()->validators($this->params);
        if (($this->params['c'] ?? '') == 'check') {
            $restTime = (int)$this->params['rest_time'];
            $reduceTime = (int)$this->params['reduce_time'];
            if ($reduceTime >= $restTime) {
                return $this->outputSuccess([
                    'is_confirm'   => 1,
                    'confirm_text' => "将会立刻失效",
                ]);
            }
            return $this->outputSuccess([
                'is_confirm'   => 0,
                'confirm_text' => "",
            ]);
        }
        $data = $this->service->reduce($this->params);
        return $this->outputSuccess($data);
    }
}