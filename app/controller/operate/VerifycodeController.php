<?php

namespace Imee\Controller\Operate;

use Imee\Controller\BaseController;
use Imee\Service\Operate\VerifyCodeService;

class VerifycodeController extends BaseController
{
    /**
     * @var VerifyCodeService
     */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new VerifyCodeService();
    }

    /**
     * @page verifycode
     * @name 登录验证码
     */
    public function mainAction()
    {
    }

    /**
     * @page verifycode
     * @point 列表
     */
    public function listAction()
    {
        $res = $this->service->getListAndTotal($this->params);
        return $this->outputSuccess($res['data'] ?? [], ['total' => $res['total'] ?? 0]);
    }

    /**
     * @page verifycode
     * @point 创建
     * @logRecord(content = '创建', action = '0', model = 'verifycode', model_id = 'id')
     */
    public function createAction()
    {
        //兼容浏览器异常uid变大写
        if (!empty($this->params['UID']) && empty($this->params['uid'])) {
            $this->params['uid'] = $this->params['UID'];
        }
        if (empty($this->params['uid'])) {
            return $this->outputError(-1, 'uid 必须');
        }
        [$result, $id] = $this->service->create($this->params['uid'], $this->params['admin_id']);
        if (!$result) {
            return $this->outputError(-1, $id);
        }
        return $this->outputSuccess(['id' => $id, 'after_json' => $this->params]);
    }
}