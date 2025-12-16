<?php

namespace Imee\Controller\Operate\User;

use Imee\Controller\BaseController;
use Imee\Service\Operate\User\MobileWhiteListService;
use Imee\Service\Operate\User\UserPlatformService;

class MobilewhitelistController extends BaseController
{
    /**
     * @var MobileWhiteListService $service
     */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new MobileWhiteListService();
    }

    /**
     * @page mobilewhitelist
     * @name 用户管理-用户手机号-查看白名单
     */
    public function mainAction()
    {
    }

    /**
     * @page  mobilewhitelist
     * @point 列表
     */
    public function listAction()
    {
        $res = $this->service->getListAndTotal($this->params);
        return $this->outputSuccess($res['data'] ?? [], ['total' => $res['total'] ?? 0]);
    }

    /**
     * @page  mobilewhitelist
     * @point 创建
     * @logRecord(content = "删除", action = "0", model = "mobilewhitelist", model_id = "id")
     */
    public function createAction()
    {
        list($flg, $rec) = $this->service->add($this->params);
        return $flg ? $this->outputSuccess($rec) : $this->outputError(-1, $rec);
    }

    /**
     * @page  mobilewhitelist
     * @point 删除
     * @logRecord(content = "删除", action = "2", model = "mobilewhitelist", model_id = "id")
     */
    public function deleteAction()
    {
        list($flg, $rec) = $this->service->delete($this->params);
        return $flg ? $this->outputSuccess($rec) : $this->outputError(-1, $rec);
    }


}