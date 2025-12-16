<?php

namespace Imee\Controller\Operate;

use Imee\Controller\BaseController;
use Imee\Models\Xsst\XsstTeenPattiConfig;
use Imee\Service\Operate\Play\Teenpatti\TeenPattiConfigService;

class TeenpattiplayscaleController extends BaseController
{
    /** @var TeenPattiConfigService $service */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new TeenPattiConfigService();
    }

    /**
     * @page teenpattiplayscale
     * @name -Teen Patti玩法档位配置
     */
    public function mainAction()
    {
    }

    /**
     * @page teenpattiplayscale
     * @point 列表
     */
    public function listAction()
    {
        $list = $this->service->getList($this->params, XsstTeenPattiConfig::SCALE_CONFIG);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }

    /**
     * @page teenpattiplayscale
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'teenpattiplayscale', model_id = 'id')
     */
    public function modifyAction()
    {
        if (!isset($this->params['id']) || empty($this->params['id'])) {
            return $this->outputError(-1, 'ID必传');
        }
        [$res, $msg] = $this->service->editScaleConfig($this->params);
        if (!$res) {
            return $this->outputError(-1, $msg);
        }

        return $this->outputSuccess($msg);
    }
}