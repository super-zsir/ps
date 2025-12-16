<?php

namespace Imee\Controller\Audit;

use Imee\Controller\BaseController;
use Imee\Export\SensitiveExport;
use Imee\Service\Domain\Service\Audit\SensitiveService;
use Imee\Controller\Validation\Audit\Sensitive\ListValidation;
use Imee\Controller\Validation\Audit\Sensitive\RemoveValidation;
use Imee\Controller\Validation\Audit\Sensitive\AddValidation;
use Imee\Controller\Validation\Audit\Sensitive\ModifyValidation;

/**
 * 敏感词管理
 */
class SensitiveController extends BaseController
{
    /**
     * @page sensitive
     * @name 敏感词管理-敏感词管理(新)
     * @point 列表
     */
    public function indexAction()
    {
        ListValidation::make()->validators($this->request->get());

        $service = new SensitiveService();
        $res = $service->getList($this->request->get());
        return $this->outputSuccess($res['data'], array('total' => $res['total']));
    }

    /**
     * @page sensitive
     * @point 配置数据
     */
    public function configAction()
    {
        $service = new SensitiveService();
        return $this->outputSuccess($service->getConfig());
    }

    /**
     * @page sensitive
     * @point 删除数据
     */
    public function removeAction()
    {
        RemoveValidation::make()->validators($this->request->getPost());
        $service = new SensitiveService();
        $service->remove($this->request->getPost());
        return $this->outputSuccess();
    }

    /**
     * @page sensitive
     * @point 新增
     */
    public function addAction()
    {
        AddValidation::make()->validators($this->request->getPost());

        $service = new SensitiveService();
        $service->add($this->request->getPost());
        return $this->outputSuccess();
    }

    /**
     * @page sensitive
     * @point 修改
     */
    public function modifyAction()
    {
        ModifyValidation::make()->validators($this->request->getPost());

        $service = new SensitiveService();
        $service->modify($this->request->getPost());
        return $this->outputSuccess();
    }

    /**
     * @page sensitive
     * @point 导出
     */
    public function exportAction()
    {
        return $this->syncExportWork('sensitiveExport', SensitiveExport::class, $this->params);
    }
}
