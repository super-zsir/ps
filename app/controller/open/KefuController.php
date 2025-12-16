<?php

namespace Imee\Controller\Open;

use Imee\Controller\BaseOpenController;
use Imee\Service\Domain\Service\Cs\WorkorderService;
use Imee\Service\Super\SuperService;

/**
 * 客服
 */
class KefuController extends BaseOpenController
{
    public $params;

    public function onConstruct()
    {
        parent::onConstruct();
        $get = $this->request->getQuery();
        $post = $this->request->getPost();
        $put = $this->request->getPut();
        $this->params = array_merge(
            $get,
            $post,
            $put
        );
        $body = $this->request->getRawBody();
        $body = @json_decode($body, true);
        if (is_array($body)) {
            $this->params = array_merge($this->params, $body);
        }
        $this->checkAuth();
    }

    /**
     * 用户信息
     * @return mixed
     */
    public function userInfoAction()
    {
        $res = WorkorderService::getInstance()->userInfo($this->params);
        return $this->outputSuccess($res);
    }

    public function appDebugAction()
    {
        $res = WorkorderService::getInstance()->appDebug($this->params);
        return $this->outputSuccess($res);
    }

    public function addAccountAction()
    {
        $res = SuperService::getInstance()->addAccount($this->params);
        return $this->outputSuccess($res);
    }

    public function superAccountAction()
    {
        $res = SuperService::getInstance()->superAccount($this->params);
        return $this->outputSuccess($res);
    }
}