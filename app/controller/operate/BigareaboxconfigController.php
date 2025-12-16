<?php

namespace Imee\Controller\Operate;

use Imee\Controller\BaseController;
use Imee\Service\Operate\Play\Greedy\BigareaBoxParamsService;

class BigareaboxconfigController extends BaseController
{
    /** @var BigareaBoxParamsService $service */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new BigareaBoxParamsService();
    }

    /**
     * @page bigareaboxconfig
     * @name 大区宝箱配置
     */
    public function mainAction()
    {
    }

    /**
     * @page bigareaboxconfig
     * @point 列表
     */
    public function listAction()
    {
        $res = $this->service->getList($this->params);
        return $this->outputSuccess($res);
    }

    /**
     * @page bigareaboxconfig
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'bigareaboxconfig', model_id = 'id')
     */
    public function modifyAction()
    {
        [$result, $data] = $this->service->modify($this->params);
        if (!$result) {
            return $this->outputError(-1, $data);
        }
        return $this->outputSuccess($data);
    }
}