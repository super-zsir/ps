<?php

namespace Imee\Controller\Operate\Topcard;

use Imee\Controller\BaseController;
use Imee\Service\Operate\Topcard\RoomTopCardConfigService;

class RoomtopcardconfigController extends BaseController
{
    /**
     * @var RoomTopCardConfigService $service
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new RoomTopCardConfigService();
    }

    /**
     * @page roomtopcardconfig
     * @name 置顶卡配置
     */
    public function mainAction()
    {
    }

    /**
     * @page roomtopcardconfig
     * @point 列表
     */
    public function listAction()
    {
        $list = $this->service->getList($this->params);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }

    /**
     * @page roomtopcardconfig
     * @point 创建
     * @logRecord(content = '创建', action = '0', model = 'roomtopcardconfig', model_id = 'id')
     */
    public function createAction()
    {
        list($res, $msg) = $this->service->create($this->params);
        if (!$res) {
            return $this->outputError(-1, $msg);
        }
        return $this->outputSuccess($msg);
    }

    /**
     * @page roomtopcardconfig
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'roomtopcardconfig', model_id = 'id')
     */
    public function modifyAction()
    {
        list($res, $msg) = $this->service->modify($this->params);
        if (!$res) {
            return $this->outputError(-1, $msg);
        }
        return $this->outputSuccess($msg);
    }

    /**
     * @page roomtopcardconfig
     * @point 删除
     * @logRecord(content = '删除', action = '2', model = 'roomtopcardconfig', model_id = 'id')
     */
    public function deleteAction()
    {
        list($res, $msg) = $this->service->delete($this->params);
        if (!$res) {
            return $this->outputError(-1, $msg);
        }
        return $this->outputSuccess($msg);
    }
}