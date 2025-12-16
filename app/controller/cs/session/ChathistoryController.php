<?php
namespace Imee\Controller\Cs\Session;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Message\ListValidation;
use Imee\Service\Domain\Context\Message\ListContext;
use Imee\Service\Domain\Service\Message\ChatMessageService;

class ChathistoryController extends BaseController
{
    /**
     * @page session.chat
     * @point 聊天历史-列表
     */
    public function indexAction()
    {
        ListValidation::make()->validators($this->request->get());
        $context = new ListContext($this->request->get());
        $service = new ChatMessageService();
        $res = $service->getList($context);
        return $this->outputSuccess($res['data'], array('total' => $res['total']));
    }
}