<?php

namespace Imee\Controller\Operate;

use Imee\Controller\BaseController;
use Imee\Service\Operate\NocodeTestService;
use Imee\Export\Operate\NocodeTestExport;
use Imee\Controller\Validation\Operate\Nocodetest\CreateValidation;
use Imee\Controller\Validation\Operate\Nocodetest\ModifyValidation;
use Imee\Controller\Validation\Operate\Nocodetest\DeleteValidation;
use Imee\Controller\Validation\Operate\Nocodetest\DeleteBatchValidation;
use Imee\Controller\Validation\Operate\Nocodetest\PhoneValidation;
use Imee\Controller\Validation\Operate\Nocodetest\TagValidation;

class NocodetestController extends BaseController
{
    /**
     * @var NocodeTestService $service
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new NocodeTestService();
    }

    /**
     * @page nocodetest
     * @name 零代码测试
     */
    public function mainAction()
    {

    }

    /**
     * @page nocodetest
     * @point 列表
     */
    public function listAction()
    {
        $list = $this->service->getListAndTotal($this->params);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }

    /**
     * @page nocodetest
     * @point 创建
     * @logRecord(content = '创建', action = '0', model = 'nocodetest', model_id = 'uid')
     */
    public function createAction()
    {
        CreateValidation::make()->validators($this->params);
        $data = $this->service->create($this->params);
        return $this->outputSuccess($data);
    }

    /**
     * @page nocodetest
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'nocodetest', model_id = 'uid')
     */
    public function modifyAction()
    {
        ModifyValidation::make()->validators($this->params);
        $data = $this->service->modify($this->params);
        return $this->outputSuccess($data);
    }

    /**
     * @page nocodetest
     * @point 删除
     * @logRecord(content = '删除', action = '2', model = 'nocodetest', model_id = 'uid')
     */
    public function deleteAction()
    {
        DeleteValidation::make()->validators($this->params);
        $data = $this->service->delete($this->params);
        return $this->outputSuccess($data);
    }

    /**
     * @page nocodetest
     * @point 批量删除
     * @logRecord(content = '批量删除', action = '2', model = 'nocodetest', model_id = 'uid')
     */
    public function deleteBatchAction()
    {
        DeleteBatchValidation::make()->validators($this->params);
        $data = $this->service->deleteBatch($this->params);
        return $this->outputSuccess($data);
    }

    /**
     * @page nocodetest
     * @point 详情
     */
    public function infoAction()
    {
        $data = $this->service->info($this->params);
        return $this->outputSuccess($data);
    }

    /**
     * @page nocodetest
     * @point 修改手机号
     * @logRecord(content = '修改手机号', action = '1', model = 'nocodetest', model_id = 'uid')
     */
    public function phoneAction()
    {
        PhoneValidation::make()->validators($this->params);
        $data = $this->service->phone($this->params);
        return $this->outputSuccess($data);
    }

    /**
     * @page nocodetest
     * @point 标签
     * @logRecord(content = '标签', action = '1', model = 'nocodetest', model_id = 'uid')
     */
    public function tagAction()
    {
        TagValidation::make()->validators($this->params);
        return $this->outputSuccess([]);
    }

    /**
     * @page nocodetest
     * @point 导出
     */
    public function exportAction()
    {
        return $this->syncExportWork('nocodetest', NocodeTestExport::class, $this->params);
    }
}