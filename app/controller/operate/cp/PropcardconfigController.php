<?php

namespace Imee\Controller\Operate\Cp;

use Imee\Controller\BaseController;
use Imee\Service\Operate\Cp\PropCardConfigService;

class PropcardconfigController extends BaseController
{

    /** @var PropCardConfigService $service */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new PropCardConfigService();
    }

    /**
     * @page propcardconfig
     * @name 道具配置
     */
    public function mainAction()
    {
    }

    /**
     * @page propcardconfig
     * @point 列表
     */
    public function listAction()
    {
        $data = $this->service->getListAndTotal($this->params);
        return $this->outputSuccess($data['data'], ['total' => $data['total']]);
    }

    /**
     * @page propcardconfig
     * @point 创建
     * @logRecord(content = '创建', action = '0', model = 'propcardconfig', model_id = 'id')
     */
    public function createAction()
    {
        list($flg, $rec) = $this->service->add($this->params);
        return $flg ? $this->outputSuccess($rec) : $this->outputError(-1, $rec);
    }

    /**
     * @page propcardconfig
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'propcardconfig', model_id = 'id')
     */
    public function modifyAction()
    {
        list($flg, $rec) = $this->service->modify($this->params);
        return $flg ? $this->outputSuccess($rec) : $this->outputError(-1, $rec);
    }

    /**
     * @page propcardconfig
     * @point 删除
     * @logRecord(content = '删除', action = '2', model = 'propcardconfig', model_id = 'id')
     */
    public function deleteAction()
    {
        list($flg, $rec) = $this->service->delete($this->params);
        return $flg ? $this->outputSuccess($rec) : $this->outputError(-1, $rec);
    }

}