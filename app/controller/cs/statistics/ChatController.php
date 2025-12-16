<?php

namespace Imee\Controller\Cs\Statistics;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Cs\Statistics\Chat\ListValidation;

use Imee\Service\Domain\Context\Cs\Statistics\Chat\ListContext;

use Imee\Service\Domain\Service\Cs\Statistics\ChatService;

/**
 * 客服满意度统计
 */
class ChatController extends BaseController
{
    /**
     * @page statistics.chat
     * @name 客服系统-客服业务数据-客服满意度统计
     * @point 列表
     */
    public function indexAction()
    {
        ListValidation::make()->validators($this->request->get());
        $context = new ListContext($this->request->get());
        $service = new ChatService();
        $result = $service->getList($context);
        return $this->outputSuccess($result['data'], array('total' => $result['total']));
    }

    /**
     * @page statistics.chat
     * @point 配置数据
     */
    public function configAction()
    {
        $service = new ChatService();
        return $this->outputSuccess($service->getConfig());
    }
}
