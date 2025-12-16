<?php

namespace Imee\Controller\Operate\Emoticons;

use Imee\Controller\BaseController;
use Imee\Service\Operate\Emoticons\EmoticonsService;

class EmoticonsidentitylistController extends BaseController
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
	 * @page emoticonsidentitylist
	 * @name 发送人群ID
	 */
    public function mainAction()
    {
    }
    
    /**
	 * @page emoticonsidentitylist
	 * @point 列表
	 */
    public function listAction()
    {
        $data = $this->service->getIdentityList($this->params);
        return $this->outputSuccess($data['data'] ?? [], ['total' => $data['total'] ?? 0]);
    }
}