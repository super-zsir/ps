<?php

namespace Imee\Controller\Operate;

use Imee\Controller\BaseController;
use Imee\Service\Operate\ShareInfoService;

class ShareinfoController extends BaseController
{
    /**
     * @var ShareInfoService
     */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new ShareInfoService();
    }

    /**
     * @page shareinfo
     * @name 运营系统-分享文本管理
     */
    public function mainAction()
    {
    }

    /**
     * @page  shareinfo
     * @point 列表
     */
    public function listAction()
    {
        $res = $this->service->getListAndTotal($this->params, $this->params['page'], $this->params['limit']);
        return $this->outputSuccess($res['data'] ?? [], ['total' => $res['total'] ?? 0]);
    }

    /**
     * @page  shareinfo
     * @point 创建
     * @logRecord(content = '创建', action = '0', model = 'shareinfo', model_id = 'id')
     */
    public function createAction()
    {
        $res = $this->service->add($this->params);
        return $this->outputSuccess($res);
    }

    /**
     * @page  shareinfo
     * @point 编辑
     * @logRecord(content = '编辑', action = '1', model = 'shareinfo', model_id = 'id')
     */
    public function modifyAction()
    {
        if (empty($this->params['id'])) {
            return $this->outputError(-1, 'id必传');
        }
        $res = $this->service->edit($this->params['id'], $this->params);

        return $this->outputSuccess($res);
    }
}