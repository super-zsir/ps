<?php

namespace Imee\Controller\Operate\Card;

use Imee\Controller\BaseController;
use Imee\Service\Operate\Minicard\MiniCardUserService;
use Imee\Models\Xs\XsItemCard;

class HomepagecarduserController extends BaseController
{
    /** @var MiniCardUserService */
    private $service;
    public function onConstruct()
    {
        parent::onConstruct();
        $this->params['type'] = XsItemCard::TYPE_HOMEPAGE;
        $this->service = new MiniCardUserService();
    }
    
    /**
     * @page homepagecarduser
     * @name 个人主页装扮卡片管理
     */
    public function mainAction()
    {
    }
    
    /**
     * @page homepagecarduser
     * @point 列表
     */
    public function listAction()
    {
        $data = $this->service->getListAndTotal($this->params, $this->params['page'] ?? 1, $this->params['limit'] ?? 15);
        return $this->outputSuccess($data['data'] ?? [], ['total' => $data['total'] ?? 0]);
    }
    
    /**
     * @page homepagecarduser
     * @point 失效
     * @logRecord(content = '失效', action = '1', model = 'homepagecarduser', model_id = 'id')
     */
    public function invalidAction()
    {
        $data = $this->service->invalid($this->params);
        return $this->outputSuccess($data);
    }
}