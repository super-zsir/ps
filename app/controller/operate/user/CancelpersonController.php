<?php

namespace Imee\Controller\Operate\User;

use Imee\Controller\BaseController;
use Imee\Service\Operate\User\CancelPersonService;

class CancelpersonController extends BaseController
{
    /**
     * @var CancelPersonService $service
     */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new CancelPersonService();
    }

    /**
     * @page cancelperson
     * @name 运营系统-用户管理-注销管理
     */
    public function mainAction()
    {
    }

    /**
     * @page  cancelperson
     * @point list
     */
    public function listAction()
    {
        $params = $this->params;
        $c = trim($params['c'] ?? '');
        switch ($c) {
            case 'log':
                $data = $this->service->getLogListAndTotal($this->params);
                return $this->outputSuccess($data['data'], ['total' => $data['total']]);
            default:
                $data = $this->service->getListAndTotal($this->params);
                return $this->outputSuccess($data['data'], ['total' => $data['total']]);
        }

    }

    /**
     * @page  cancelperson
     * @point modify
     * @logRecord(content = "修改", action = "1", model = "cancelperson", model_id = "id")
     */
    public function modifyAction()
    {
        list($flg, $rec) = $this->service->modify($this->params);
        return $flg ? $this->outputSuccess($rec) : $this->outputError(-1, $rec);
    }


}