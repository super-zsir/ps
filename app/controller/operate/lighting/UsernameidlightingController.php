<?php

namespace Imee\Controller\Operate\Lighting;

use Imee\Controller\BaseController;
use Imee\Service\Operate\Lighting\UserNameIdLightingService;

class UsernameidlightingController extends BaseController
{
    /**
     * @var UserNameIdLightingService $service
     */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new UserNameIdLightingService();
    }


    /**
     * @page usernameidlighting
     * @name 用户炫彩资源管理
     */
    public function mainAction()
    {
    }

    /**
     * @page  usernameidlighting
     * @point 列表
     */
    public function listAction()
    {
        $data = $this->service->getListAndTotal($this->params);
        return $this->outputSuccess($data['data'], ['total' => $data['total']]);
    }


    /**
     * @page  usernameidlighting
     * @point 立即失效
     * @logRecord(content = '立即失效', action = '1', model = 'usernameidlighting', model_id = 'id')
     */
    public function invalidAction()
    {
        list($flg, $rec) = $this->service->invalid($this->params);
        return $flg ? $this->outputSuccess($rec) : $this->outputError(-1, $rec);
    }


}