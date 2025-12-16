<?php

namespace Imee\Controller\Operate\Emoticons;

use Imee\Controller\BaseController;
use Imee\Service\Operate\Emoticons\InteractiveEmoticonService;

class EnteractiveemoticonsController extends BaseController
{
    /**
     * @var InteractiveEmoticonService $service
     */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new InteractiveEmoticonService();
    }

    /**
     * @page enteractiveemoticons
     * @name 互动表情素材
     */
    public function mainAction()
    {
    }

    /**
     * @page  enteractiveemoticons
     * @point 列表
     */
    public function listAction()
    {
        $params = $this->params;
        $c = trim($params['c'] ?? '');
        switch ($c) {
            case 'options':
                return $this->outputSuccess($this->service->getOptions());
            case 'info':
                return $this->outputSuccess($this->service->getInfo($this->params));
            default:
                $data = $this->service->getListAndTotal($this->params);
                return $this->outputSuccess($data['data'], ['total' => $data['total']]);
        }
    }

    /**
     * @page  enteractiveemoticons
     * @point 创建
     * @logRecord(content = '创建', action = '0', model = 'enteractiveemoticons', model_id = 'id')
     */
    public function createAction()
    {
        list($flg, $rec) = $this->service->add($this->params);
        return $flg ? $this->outputSuccess($rec) : $this->outputError(-1, $rec);
    }

    /**
     * @page  enteractiveemoticons
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'enteractiveemoticons', model_id = 'id')
     */
    public function modifyAction()
    {
        list($flg, $rec) = $this->service->modify($this->params);
        return $flg ? $this->outputSuccess($rec) : $this->outputError(-1, $rec);
    }


    /**
     * @page  enteractiveemoticons
     * @point 上下架
     * @logRecord(content = '上下架', action = '1', model = 'enteractiveemoticons', model_id = 'id')
     */
    public function upShelfAction()
    {
        list($flg, $rec) = $this->service->upShelf($this->params);
        return $flg ? $this->outputSuccess($rec) : $this->outputError(-1, $rec);
    }
}