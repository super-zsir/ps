<?php

namespace Imee\Controller\Operate\User;

use Imee\Controller\BaseController;
use Imee\Service\Operate\User\UserPlatformService;

class UserplatformController extends BaseController
{
    /**
     * @var UserPlatformService $service
     */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new UserPlatformService();
    }

    /**
     * @page userplatform
     * @name 用户管理-用户手机号
     */
    public function mainAction()
    {
    }

    /**
     * @page  userplatform
     * @point list
     */
    public function listAction()
    {
        $params = $this->params;
        $c = $params['c'] ?? '';

        switch ($c) {
            case 'detail':
                $data = $this->service->getDetailListAndTotal($this->params);
                return $this->outputSuccess($data['data'], ['total' => $data['total']]);
            default:
                $data = $this->service->getListAndTotal($this->params);
                return $this->outputSuccess($data['data'], ['total' => $data['total']]);
        }
    }

    /**
     * @page  userplatform
     * @point 修改手机号
     * @logRecord(content = "修改手机号", action = "0", model = "userplatform", model_id = "uid")
     */
    public function modifyPhoneAction()
    {
        list($flg, $rec) = $this->service->modifyPhone($this->params);
        return $flg ? $this->outputSuccess($rec) : $this->outputError(-1, $rec);
    }

    /**
     * @page  userplatform
     * @point 修改安全手机号
     * @logRecord(content = "修改安全手机号", action = "1", model = "userplatform", model_id = "uid")
     */
    public function modifySafePhoneAction()
    {
        list($flg, $rec) = $this->service->modifySafePhone($this->params);
        return $flg ? $this->outputSuccess($rec) : $this->outputError(-1, $rec);
    }

    /**
     * @page  userplatform
     * @point 绑定手机号并生成随机密码
     * @logRecord(content = "绑定手机号并生成随机密码", action = "2", model = "userplatform", model_id = "uid")
     */
    public function bindPhoneAction()
    {
        list($flg, $rec) = $this->service->bindPhone($this->params);
        return $flg ? $this->outputSuccess($rec) : $this->outputError(-1, $rec);
    }
}