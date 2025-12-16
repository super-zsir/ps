<?php

namespace Imee\Controller\Operate;

use Imee\Controller\BaseController;
use Imee\Service\Operate\WelcomebigareaService;

class WelcomebigareaController extends BaseController
{
    /**
     * @var WelcomebigareaService
     */
    private $service;
    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new WelcomebigareaService();
    }

    /**
     * @page welcomebigarea
     * @name 迎新礼包管理-礼包大区开关
     */
    public function mainAction()
    {
    }

    /**
     * @page welcomebigarea
     * @point 列表
     */
    public function listAction()
    {
        $res = $this->service->getList($this->params);
        return $this->outputSuccess($res['data'] ?? [], ['total' => $res['total'] ?? 0]);
    }

    /**
     * @page welcomebigarea
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'welcomebigarea', model_id = 'id')
     */
    public function modifyAction()
    {
        [$result, $data] = $this->service->modify($this->params['id'], $this->params['invite_gift_switch']);
        if (!$result) {
            return $this->outputError(-1, $data);
        }
        return $this->outputSuccess($data);
    }
}
