<?php

namespace Imee\Controller\Operate\Push;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Push\PushCateAddValidation;
use Imee\Service\Operate\Push\PushService;

class PushcateController extends BaseController
{
    /**
     * @var PushService
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new PushService();
    }

    /**
     * @page pushcate
     * @name push类型管理
     */
    public function mainAction()
    {
    }

    /**
     * @page pushcate
     * @point 列表
     */
    public function listAction()
    {
        $list = $this->service->getCateList($this->params);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }

    /**
     * @page pushcate
     * @point 创建
     * @logRecord(content = '创建', action = '0', model = 'pushcate', model_id = 'cid')
     */
    public function createAction()
    {
        PushCateAddValidation::make()->validators($this->params);
        [$cid, $msg] = $this->service->addCate($this->params);
        if (!$cid) {
            return $this->outputError(-1, $msg);
        }
        return $this->outputSuccess(['cid' => $cid, 'after_json' => $this->params]);
    }

    /**
     * @page pushcate
     * @point 删除
     * @logRecord(content = '删除', action = '2', model = 'pushcate', model_id = 'cid')
     */
    public function deleteAction()
    {
        $cid = $this->params['cid'] ?? 0;
        if (empty($cid)) {
            return $this->outputError(-1, 'CID错误');
        }
        [$result, $msg] = $this->service->deleteCate($cid);
        if (!$result) {
            return $this->outputError(-1, $msg);
        }
        return $this->outputSuccess();
    }
}