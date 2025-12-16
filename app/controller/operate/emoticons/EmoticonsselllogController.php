<?php

namespace Imee\Controller\Operate\Emoticons;

use Imee\Controller\BaseController;
use Imee\Service\Operate\Emoticons\EmoticonsService;

class EmoticonsselllogController extends BaseController
{
    /**
     * @var EmoticonsService $service
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new EmoticonsService();
    }
    
    /**
	 * @page emoticonsselllog
	 * @name  表情包购买记录
	 */
    public function mainAction()
    {
    }
    
    /**
	 * @page emoticonsselllog
	 * @point 列表
	 */
    public function listAction()
    {
        $data = $this->service->getSellLogList($this->params);
        return $this->outputSuccess($data['data'] ?? [], ['total' => $data['total'] ?? 0]);
    }
}