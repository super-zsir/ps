<?php

namespace Imee\Controller\Operate\Pretty;

use Imee\Controller\BaseController;
use Imee\Models\Xs\XsBigarea;
use Imee\Service\Operate\ShowOriginUidSwitch;

class ShoworiginuidswitchController extends BaseController
{
    /**
     * @var ShowOriginUidSwitch
     */
    private $service;

    private $type;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new ShowOriginUidSwitch();
        $this->type = XsBigarea::SHOW_ORIGIN_UID_SWITCH;
    }

    /**
     * @page showoriginuidswitch
     * @name 是否展示原始ID
     */
    public function mainAction()
    {
    }

    /**
     * @page showoriginuidswitch
     * @point 列表
     */
    public function listAction()
    {
        $data = $this->service->getList($this->type);
        return $this->outputSuccess($data['data'] ?? [], ['total' => $data['total'] ?? 0]);
    }

    /**
     * @page showoriginuidswitch
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'showoriginuidswitch', model_id = 'bigarea_id')
     */
    public function modifyAction()
    {
        $switch = $this->params['show_origin_uid_switch'] ?? 0;
        $id = $this->params['bigarea_id'] ?? 0;
        if (empty($id)) {
            return $this->outputError(-1, 'ID错误');
        }
        [$res, $msg] = $this->service->edit($switch, $id, $this->type);
        if (!$res) {
            return $this->outputError(-1, $msg);
        }
        return $this->outputSuccess($this->params);
    }

}