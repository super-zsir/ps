<?php

namespace Imee\Controller\Cs\Statistics;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Cs\Statistics\ManualChatService\ManualChatServiceValidation;
use Imee\Service\Domain\Service\Cs\Statistics\ManualChatServiceService;

/**
 * 客服系统统计
 */
class ManualchatserviceController extends BaseController
{
    /**
     * @page statistics.manualChatService
     * @name 客服系统-客服业务数据-人工客服数据
     * @point 列表
     */
    public function indexAction()
    {
        ManualChatServiceValidation::make()->validators($this->request->get());
        $service = new ManualChatServiceService();
        $result = $service->getManualChatServiceList($this->request->get());
        return $this->outputSuccess($result['data'], array('total' => $result['total']));
    }

    /**
     * @page statistics.manualChatService
     * @point 配置数据
     */
    public function configAction()
    {
        $service = new ManualChatServiceService();
        return $this->outputSuccess($service->getManualChatServiceConfig());
    }

    // /**
    //  * @page statistics.manualChatService
    //  * @point 数据导出
    //  */
    // public function exportAction()
    // {
    //     $service = new ManualChatServiceService();
    //     return $service->export();
    // }
}
