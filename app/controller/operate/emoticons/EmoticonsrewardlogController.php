<?php

namespace Imee\Controller\Operate\Emoticons;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Emoticons\Reward\AddValidation;
use Imee\Helper\Traits\ImportTrait;
use Imee\Service\Operate\Emoticons\EmoticonsRewardService;

class EmoticonsrewardlogController extends BaseController
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
     * @page emoticonsrewardlog
     * @name 表情包下发回收日志
     */
    public function mainAction()
    {
    }
    
    /**
     * @page emoticonsrewardlog
     * @point 列表
     */
    public function listAction()
    {
        $this->params['reward_type'] = 2; //2 回收
        $data = $this->service->getList($this->params);
        return $this->outputSuccess($data['data'] ?? [], ['total' => $data['total'] ?? 0]);
    }
}