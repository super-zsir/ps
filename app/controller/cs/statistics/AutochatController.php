<?php

namespace Imee\Controller\Cs\Statistics;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Cs\Statistics\AutoChat\DetailValidation;
use Imee\Controller\Validation\Cs\Statistics\AutoChat\ListValidation;
use Imee\Service\Domain\Service\Cs\Statistics\AutoChatService;

/**
 * 自动回复数据统计
 */
class AutochatController extends BaseController
{
    /**
     * @page statistics.autoChat
     * @name 客服系统-自动应答-自动回复数据统计
     * @point 列表
     */
    public function indexAction()
    {
        ListValidation::make()->validators($this->request->get());
        $service = new AutoChatService();
        $result = $service->getList($this->request->get());
        return $this->outputSuccess($result['data'], array('total' => $result['total']));
    }

    /**
     * @page statistics.autoChat
     * @point 自动回复数据统计-配置数据
     */
    public function configAction()
    {
        $service = new AutoChatService();
        return $this->outputSuccess($service->getConfig());
    }

    /**
     * @page statistics.autoChat
     * @point 自动回复数据统计-详情数据
     */
    public function detailAction()
    {
        DetailValidation::make()->validators($this->request->get());
        $service = new AutoChatService();
        $result = $service->detail($this->request->get());
        return $this->outputSuccess($result['data'], array('total' => $result['total']));
    }
}
