<?php

namespace Imee\Controller\Operate\Pretty;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Pretty\User\HistoryValidation;

use Imee\Service\Domain\Service\Pretty\PrettyuserService;

class PrettyinfologController extends BaseController
{
    public function onConstruct()
    {
        parent::onConstruct();
    }

    // /**
    //  * @page prettyinfolog
    //  * @name -用户靓号变更记录表
    //  */
    // public function mainAction()
    // {
    // }

    /**
     * @page prettyuser
     * @point 变更记录
     */
    public function listAction()
    {
        $params = $this->trimParams($this->request->get());
        
        HistoryValidation::make()->validators($params);
        $service = new PrettyuserService;
        $res = $service->getInfoLog($params);
        return $this->outputSuccess($res['data'], array('total' => $res['total']));
    }
}
