<?php

namespace Imee\Controller\Operate\Minicard;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Minicard\MiniCardAddValidation;
use Imee\Models\Xs\XsItemCard;
use Imee\Service\Operate\Minicard\MiniCardService;

class MinicardController extends BaseController
{
    /** @var MiniCardService */
    private $service;
    public function onConstruct()
    {
        parent::onConstruct();
        $this->params['type'] = XsItemCard  ::TYPE_MINI;
        $this->service = new MiniCardService();
    }
    
    /**
     * @page minicard
     * @name mini卡装扮配置
     */
    public function mainAction()
    {
    }
    
    /**
     * @page minicard
     * @point 列表
     */
    public function listAction()
    {
        $c = $this->params['c'] ?? '';
        if ($c == 'config') {
            return $this->outputSuccess(['languages' => XsItemCard::$languageMap]);
        }
        $data = $this->service->getListAndTotal($this->params, $this->params['page'] ?? 1, $this->params['limit'] ?? 15);
        return $this->outputSuccess($data['data'] ?? [], ['total' => $data['total'] ?? 0]);
    }
    
    /**
     * @page minicard
     * @point 创建
     * @logRecord(content = '创建', action = '0', model = 'minicard', model_id = 'id')
     */
    public function createAction()
    {
        $params = $this->trimParams($this->params);
        MiniCardAddValidation::make()->validators($params);

        $data = $this->service->create($params);
        return $this->outputSuccess($data);
    }
    
    /**
     * @page minicard
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'minicard', model_id = 'id')
     */
    public function modifyAction()
    {
        $params = $this->trimParams($this->params);
        MiniCardAddValidation::make()->validators($params);

        $data = $this->service->modify($params);
        return $this->outputSuccess($data);
    }

    /**
     * @page minicard
     * @point 详情
     */
    public function infoAction()
    {
        $id = $this->params['id'] ?? 0;
        if (!is_numeric($id) || $id < 1) {
            return $this->outputError(-1, '参数有误');
        }

        return $this->outputSuccess($this->service->getInfo((int)$id, $this->params['type']));
    }
}