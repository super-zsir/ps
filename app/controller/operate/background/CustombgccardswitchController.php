<?php

namespace Imee\Controller\Operate\Background;

use Imee\Controller\BaseController;
use Imee\Service\Operate\Background\Custombackground\CustomBgcCardSwitchService;

class CustombgccardswitchController extends BaseController
{
    /**
     * @var CustomBgcCardSwitchService $service
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new CustomBgcCardSwitchService();
    }

    /**
     * @page custombgccardswitch
     * @name 自定义房间背景大区开关
     */
    public function mainAction()
    {
    }

    /**
     * @page custombgccardswitch
     * @point 列表
     */
    public function listAction()
    {
        $list = $this->service->getList($this->params);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }

    /**
     * @page custombgccardswitch
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'custombgccardswitch', model_id = 'bigarea_id')
     */
    public function modifyAction()
    {
        $this->service->modify($this->params['bigarea_id'], $this->params['switch']);
        return $this->outputSuccess($this->params);
    }
}