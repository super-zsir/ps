<?php
namespace Imee\Controller\Cs\Session;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Cs\Session\Chat\ListValidation;
use Imee\Service\Domain\Context\Cs\Session\Chat\ListContext;
use Imee\Service\Domain\Service\Cs\Session\ChatService;

class ChatController extends BaseController
{
    /**
     * @page session.chat
     * @name 客服系统-会话记录-历史会话管理
     * @point 列表
     */
    public function indexAction()
    {
        ListValidation::make()->validators($this->request->get());
        $context = new ListContext($this->request->get());
        $service = new ChatService();
        $res = $service->getList($context);
        return $this->outputSuccess($res['data'], array('total' => $res['total']));
    }

    /**
     * @page session.chat
     * @point 配置
     */
    public function configAction()
    {
        $service = new ChatService();
        return $this->outputSuccess($service->getConfig());
    }
}
