<?php

namespace Imee\Controller\Operate\Honor;

use Imee\Controller\BaseController;
use Imee\Service\Operate\Honor\UserHonorLevelSendRecordService;

class UserhonorlevelsendrecordController extends BaseController
{
    /**
     * @var UserHonorLevelSendRecordService $service
     */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new UserHonorLevelSendRecordService();
    }


    /**
     * @page userhonorlevelsendrecord
     * @name 用户荣誉等级管理
     */
    public function mainAction()
    {
    }

    /**
     * @page  userhonorlevelsendrecord
     * @point 列表
     */
    public function listAction()
    {
        $data = $this->service->getListAndTotal($this->params);
        return $this->outputSuccess($data['data'], ['total' => $data['total']]);
    }


    /**
     * @page  userhonorlevelsendrecord
     * @point 立即失效
     * @logRecord(content = '立即失效', action = '1', model = 'userhonorlevelsendrecord', model_id = 'id')
     */
    public function invalidAction()
    {
        list($flg, $rec) = $this->service->invalid($this->params);
        return $flg ? $this->outputSuccess($rec) : $this->outputError(-1, $rec);
    }


}