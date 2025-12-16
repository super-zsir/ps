<?php

namespace Imee\Controller\Operate\Minicard;

use Imee\Controller\BaseController;
use Imee\Service\Operate\Minicard\MiniCardUserService;
use Imee\Models\Xs\XsItemCard;

class MinicarduserController extends BaseController
{
    /** @var MiniCardUserService */
    private $service;
    public function onConstruct()
    {
        parent::onConstruct();
        $this->params['type'] = XsItemCard::TYPE_MINI;
        $this->service = new MiniCardUserService();
    }
    
    /**
     * @page minicarduser
     * @name mini卡装扮管理
     */
    public function mainAction()
    {
    }
    
    /**
     * @page minicarduser
     * @point 列表
     */
    public function listAction()
    {
        $data = $this->service->getListAndTotal($this->params, $this->params['page'] ?? 1, $this->params['limit'] ?? 15);
        return $this->outputSuccess($data['data'] ?? [], ['total' => $data['total'] ?? 0]);
    }
    
    /**
     * @page minicarduser
     * @point 失效
     * @logRecord(content = '失效', action = '1', model = 'minicarduser', model_id = 'id')
     */
    public function invalidAction()
    {
        $data = $this->service->invalid($this->params);
        return $this->outputSuccess($data);
    }
}