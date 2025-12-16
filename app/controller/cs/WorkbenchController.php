<?php
namespace Imee\Controller\Cs;

use \Imee\Controller\BaseController;
use Imee\Controller\Validation\Cs\Workbench\ActiveServiceValidation;
use Imee\Controller\Validation\Cs\Workbench\SessionListValidation;
use Imee\Controller\Validation\Message\ChatMessageListValidation;
use Imee\Service\Domain\Context\Cs\Workbench\ActiveServiceContext;
use Imee\Service\Domain\Context\Cs\Workbench\SessionListContext;
use Imee\Service\Domain\Context\Message\ChatMessageListContext;
use Imee\Service\Domain\Service\Cs\WorkbenchService;
use Imee\Service\Domain\Service\Message\ChatMessageService;

class WorkbenchController extends BaseController
{
    /**
     * @page workbench
     * @name 客服系统-工作台
     * @point 初始化
     */
    public function chatInitAction()
    {
        $service = new WorkbenchService();
        $res = $service->chatInit();
        return $this->outputSuccess($res);
    }

    /**
     * @page workbench
     * @point 会话列表
     */
    public function chatSessionListAction()
    {
        SessionListValidation::make()->validators($this->request->get());
        $context = new SessionListContext($this->request->get());
        $service = new WorkbenchService();
        $res = $service->chatSessionList($context);
        return $this->outputSuccess($res, ['total' => count($res)]);
    }

    /**
     * @page workbench
     * @point 通道列表
     */
    public function chatIndexAction()
    {
        $service = new WorkbenchService();
        $res = $service->chatIndex();
        return $this->outputSuccess($res);
    }

    /**
     * @page workbench
     * @point 对话信息列表
     */
    public function chatMessageAction()
    {
        ChatMessageListValidation::make()->validators($this->request->get());
        $context = new ChatMessageListContext($this->request->get());
        $service = new ChatMessageService();
        $res = $service->getChatMessageList($context);
        return $this->outputSuccess($res, ['total' => count($res)]);
    }

    /**
     * @page workbench
     * @point 主动对话
     */
    public function activeServiceAction()
    {
        ActiveServiceValidation::make()->validators($this->request->get());
        $context = new ActiveServiceContext($this->request->get());
        $service = new WorkbenchService();
        $res = $service->activeService($context);
        return $this->outputSuccess($res);
    }
}
