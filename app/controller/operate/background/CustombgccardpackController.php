<?php

namespace Imee\Controller\Operate\Background;

use Imee\Controller\BaseController;
use Imee\Service\Operate\Background\Custombackground\CustomBgcCardPackService;

class CustombgccardpackController extends BaseController
{
    /**
     * @var CustomBgcCardPackService $service
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new CustomBgcCardPackService();
    }

    /**
     * @page custombgccardpack
     * @name 自定义背景卡背包
     */
    public function mainAction()
    {
    }

    /**
     * @page custombgccardpack
     * @point 列表
     */
    public function listAction()
    {
        $list = $this->service->getList($this->params);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }

    /**
     * @page custombgccardpack
     * @point 删除
     * @logRecord(content = '删除', action = '2', model = 'custombgccardpack', model_id = 'id')
     */
    public function deleteAction()
    {
        $id = $this->params['id'] ?? 0;
        if (empty($id)) {
            return $this->outputError(-1, 'ID错误');
        }
        $this->service->delete($id);
        return $this->outputSuccess($this->params);
    }
}