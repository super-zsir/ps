<?php

namespace Imee\Controller\Operate\Lighting;

use Imee\Controller\BaseController;
use Imee\Service\Operate\Lighting\NameIdLightingGroupService;

class NameidlightinggroupController extends BaseController
{
    /**
     * @var NameIdLightingGroupService $service
     */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new NameIdLightingGroupService();
    }

    /**
     * @page nameidlightinggroup
     * @name 炫彩资源配置
     */
    public function mainAction()
    {
    }

    /**
     * @page  nameidlightinggroup
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
                return $this->outputSuccess($this->service->getInfo(intval($params['group_id'])));
            default:
                $data = $this->service->getListAndTotal($this->params);
                return $this->outputSuccess($data['data'], ['total' => $data['total']]);
        }
    }

    /**
     * @page  nameidlightinggroup
     * @point 创建
     * @logRecord(content = '创建', action = '0', model = 'nameidlightinggroup', model_id = 'id')
     */
    public function createAction()
    {
        list($flg, $rec) = $this->service->add($this->params);
        return $flg ? $this->outputSuccess($rec) : $this->outputError(-1, $rec);
    }

    /**
     * @page  nameidlightinggroup
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'nameidlightinggroup', model_id = 'id')
     */
    public function modifyAction()
    {
        list($flg, $rec) = $this->service->modify($this->params);
        return $flg ? $this->outputSuccess($rec) : $this->outputError(-1, $rec);
    }

    /**
     * @page  nameidlightinggroup
     * @point 删除
     * @logRecord(content = '删除', action = '2', model = 'nameidlightinggroup', model_id = 'id')
     */
    public function deleteAction()
    {
        list($flg, $rec) = $this->service->delete($this->params);
        return $flg ? $this->outputSuccess($rec) : $this->outputError(-1, $rec);
    }
}