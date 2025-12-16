<?php

namespace Imee\Controller\Operate;

use Imee\Controller\BaseController;
use Imee\Service\Rpc\PsService;

class TeampksendrecordController extends BaseController
{
    protected function onConstruct()
    {
        parent::onConstruct();
    }

    /**
     * @page teampksendrecord
     * @name 运营系统-房间Pk数据-送礼收礼明细
     */
    public function mainAction(){}

    /**
     * @page teampksendrecord
     * @point  列表
     */
    public function listAction()
    {
        list($res, $msg, $data) = (new PsService())->getTeamPkDiamondRecordList($this->params);
        if (!$res) {
            return $this->outputError(-1, $msg);
        }
        return $this->outputSuccess($data['data'], ['total' => $data['total']]);
    }
}