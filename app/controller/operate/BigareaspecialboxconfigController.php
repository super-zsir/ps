<?php

namespace Imee\Controller\Operate;

use Imee\Controller\BaseController;
use Imee\Service\Operate\Play\Greedy\BigareaSpecialBoxParamsService;

class BigareaspecialboxconfigController extends BaseController
{
    /** @var BigareaSpecialBoxParamsService $service */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new BigareaSpecialBoxParamsService();
    }

    /**
     * @page bigareaspecialboxconfig
     * @name 白名单用户宝箱掉落配置
     */
    public function mainAction()
    {
    }

    /**
     * @page bigareaspecialboxconfig
     * @point 列表
     */
    public function listAction()
    {
        $res = $this->service->getList($this->params);
        return $this->outputSuccess($res);
    }

    /**
     * @page bigareaspecialboxconfig
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'bigareaspecialboxconfig', model_id = 'id')
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