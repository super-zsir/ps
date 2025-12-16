<?php

namespace Imee\Controller\Cs\Statistics;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Cs\Statistics\AutoChatLog\ListValidation;
use Imee\Controller\Validation\Cs\Statistics\AutoChatLog\AutoReplyValidation;
use Imee\Service\Domain\Service\Cs\Statistics\AutoChatLogService;

/**
 * 自动回复历史记录
 */
class AutochatlogController extends BaseController
{
    /**
     * @page statistics.autoChatLog
     * @name 客服系统-自动应答-自动回复数据
     * @point 列表
     */
    public function indexAction()
    {
        ListValidation::make()->validators($this->request->get());
        $service = new AutoChatLogService();
        $result = $service->getList($this->request->get());
        return $this->outputSuccess($result['data'], array('total' => $result['total']));
    }

    /**
     * @page statistics.autoChatLog
     * @point 配置数据
     */
    public function configAction()
    {
        $service = new AutoChatLogService();
        return $this->outputSuccess($service->getConfig());
    }

    /**
     * @page statistics.autoChatLog
     * @point 自动回复结果统计
     */
    public function autoReplyStatAction()
    {
        AutoReplyValidation::make()->validators($this->request->get());
        $service = new AutoChatLogService();
        $result = $service->getAutoReplyStat($this->request->get());
        return $this->outputSuccess($result['data'], array('total' => $result['total']));
    }
}
