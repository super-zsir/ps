<?php
namespace Imee\Controller\Cs\Setting;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Cs\Setting\AutoReply\CreateValidation;
use Imee\Controller\Validation\Cs\Setting\AutoReply\ListValidation;
use Imee\Controller\Validation\Cs\Setting\AutoReply\ModifyValidation;
use Imee\Controller\Validation\Cs\Setting\AutoReply\RemoveValidation;
use Imee\Service\Domain\Service\Cs\Setting\AutoReplyService;

class AutoreplyController extends BaseController
{
    /**
     * @page setting.autoReply
     * @name 客服系统-客服中心设置-自动回复设置
     * @point 客服中心设置-自动回复列表
     */
    public function indexAction()
    {
        ListValidation::make()->validators($this->request->get());
        $service = new AutoReplyService();
        $res = $service->getList($this->request->get());

        return $this->outputSuccess($res['data'], array('total' => $res['total']));
    }

    /**
     * @page setting.autoReply
     * @point 客服中心设置-自动回复新增
     */
    public function createAction()
    {
        CreateValidation::make()->validators($this->request->getPost());
        $service = new AutoReplyService();
        $service->create($this->request->getPost());
        return $this->outputSuccess();
    }

    /**
     * @page setting.autoReply
     * @point 客服中心设置-自动回复修改
     */
    public function modifyAction()
    {
        ModifyValidation::make()->validators($this->request->getPost());
        $service = new AutoReplyService();
        $service->modify($this->request->getPost());
        return $this->outputSuccess();
    }

    /**
     * @page setting.autoReply
     * @point 客服中心设置-自动回复删除
     */
    public function removeAction()
    {
        RemoveValidation::make()->validators($this->request->getPost());
        $service = new AutoReplyService();
        $service->remove($this->request->getPost());
        return $this->outputSuccess();
    }

    /**
     * @page setting.autoReply
     * @point 客服中心设置-自动回复配置
     */
    public function configAction()
    {
        $service = new AutoReplyService();
        return $this->outputSuccess($service->getConfig());
    }
}
