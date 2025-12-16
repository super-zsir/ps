<?php

namespace Imee\Controller\Operate\Pretty;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Pretty\User\HistoryValidation;

use Imee\Service\Domain\Service\Pretty\PrettyuserService;

class PrettyuserhistoryController extends BaseController
{
    public function onConstruct()
    {
        parent::onConstruct();
    }

    // /**
    //  * @page prettyuserhistory
    //  * @name -操作日志
    //  */
    // public function mainAction()
    // {
    // }

    /**
     * @page prettyuser
     * @point 历史记录
     */
    public function listAction()
    {
        $params = $this->trimParams($this->request->get());
        
        HistoryValidation::make()->validators($params);
        $service = new PrettyuserService;
        $res = $service->getHistory($params);
        return $this->outputSuccess($res['data'], array('total' => $res['total']));
    }
}
