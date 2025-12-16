<?php

namespace Imee\Controller\Operate;

use Imee\Controller\BaseController;
use Imee\Service\Operate\TeamPkRecordService;
use Imee\Service\Rpc\PsService;

class TeampkrecordblueController extends BaseController
{
    protected function onConstruct()
    {
        parent::onConstruct();
    }

    /**
     * @page teampkrecordblue
     * @name 运营系统-房间Pk数据-蓝方收礼明细
     */
    public function mainAction(){}

    /**
     * @page teampkrecordblue
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