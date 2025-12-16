<?php

namespace Imee\Controller\Operate\Honor;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Honor\HonorLevelConfigValidation;
use Imee\Service\Operate\Honor\HonorLevelConfigService;

class HonorlevelconfigController extends BaseController
{
    /**
     * @var HonorLevelConfigService $service
     */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new HonorLevelConfigService();
    }

    /**
     * @page honorlevelconfig
     * @name 荣誉等级资源配置
     */
    public function mainAction()
    {
    }

    /**
     * @page  honorlevelconfig
     * @point 列表
     */
    public function listAction()
    {
        $params = $this->params;
        $c = $params['c'] ?? '';
        switch ($c) {
            case 'options':
                return $this->outputSuccess($this->service->getOptions());
            case 'info':
                return $this->outputSuccess($this->service->getInfo(intval($params['id'])));
            case 'config':
                return $this->outputSuccess($this->service->getConfig(intval($params['honor_level'])));
            default:
                $data = $this->service->getListAndTotal($this->params);
                return $this->outputSuccess($data['data'], ['total' => $data['total']]);
        }
    }

    /**
     * @page  honorlevelconfig
     * @point 创建
     * @logRecord(content = '创建', action = '0', model = 'honorlevelconfig', model_id = 'id')
     */
    public function createAction()
    {
        HonorLevelConfigValidation::make()->validators($this->params);
        list($flg, $rec) = $this->service->add($this->params);
        return $flg ? $this->outputSuccess($rec) : $this->outputError(-1, $rec);
    }

    /**
     * @page  honorlevelconfig
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'honorlevelconfig', model_id = 'id')
     */
    public function modifyAction()
    {
        HonorLevelConfigValidation::make()->validators($this->params);
        list($flg, $rec) = $this->service->modify($this->params);
        return $flg ? $this->outputSuccess($rec) : $this->outputError(-1, $rec);
    }


}