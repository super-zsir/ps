<?php
namespace Imee\Controller\Cs\Statistics;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Cs\Statistics\ChatTypeStat\ListValidation;
use Imee\Service\Domain\Context\Cs\Statistics\ChatTypeStat\ListContext;
use Imee\Service\Domain\Service\Cs\Statistics\ChatTypeStatService;

class ChattypestatController extends BaseController
{
    /**
     * @page statistics.chatTypeStat
     * @name 客服系统-客服业务数据-会话分类统计
     * @point 列表
     */
    public function indexAction()
    {
        ListValidation::make()->validators($this->request->get());
        $context = new ListContext($this->request->get());
        $service = new ChatTypeStatService();
        $result = $service->getList($context);
        return $this->outputSuccess($result['data'], array('total' => $result['total']));
    }

    /**
     * @page statistics.chatTypeStat
     * @point 配置数据
     */
    public function configAction()
    {
        $service = new ChatTypeStatService();
        return $this->outputSuccess($service->getConfig());
    }
}