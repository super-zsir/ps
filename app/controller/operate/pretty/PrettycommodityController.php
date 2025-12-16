<?php

namespace Imee\Controller\Operate\Pretty;

use Imee\Comp\Common\Export\Service\ExportService;
use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Pretty\Commodity\ListValidation;
use Imee\Controller\Validation\Operate\Pretty\Commodity\ExportValidation;
use Imee\Controller\Validation\Operate\Pretty\Commodity\CreateValidation;
use Imee\Controller\Validation\Operate\Pretty\Commodity\ModifyValidation;
use Imee\Controller\Validation\Operate\Pretty\Commodity\ShelfValidation;
use Imee\Controller\Validation\Operate\Pretty\Commodity\InfoValidation;

use Imee\Service\Domain\Service\Pretty\PrettycommodityService;
use Imee\Export\PrettycommodityExport;

class PrettycommodityController extends BaseController
{
    /**
     * @var PrettycommodityService $service
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new PrettycommodityService;
    }

    /**
     * @page prettycommodity
     * @name -靓号商城管理
     */
    public function mainAction()
    {
    }

    /**
     * @page prettycommodity
     * @point 列表
     */
    public function listAction()
    {
        $params = $this->trimParams($this->request->get());
        
        ListValidation::make()->validators($params);
        $res = $this->service->getList($params);
        return $this->outputSuccess($res['data'], array('total' => $res['total']));
    }

    /**
     * @page prettycommodity
     * @point 创建
     */
    public function createAction()
    {
        $params = $this->trimParams($this->request->getPost());
        CreateValidation::make()->validators($params);
        $this->service->create($params);
        return $this->outputSuccess();
    }

    /**
     * @page prettycommodity
     * @point 修改
     */
    public function modifyAction()
    {
        $params = $this->trimParams($this->request->getPost());
        ModifyValidation::make()->validators($params);
        $this->service->modify($params);
        return $this->outputSuccess();
    }

    /**
     * @page prettycommodity
     * @point 导出
     */
    public function exportAction()
    {
        $params = $this->trimParams($this->request->get());
        
        ExportValidation::make()->validators($params);
        $this->params['guid'] = 'prettycommodity';
        ExportService::addTask($this->uid, 'prettycommodity.xlsx', [PrettyCommodityExport::class, 'export'], $this->params, '靓号商城管理导出');
        ExportService::showHtml();

        return $this->outputSuccess();
    }

    /**
     * @page prettycommodity
     * @point 批量上架
     */
    public function shelfonAction()
    {
        $params = $this->trimParams($this->request->getPost());
        ShelfValidation::make()->validators($params);
        $this->service->shelfon($params);
        return $this->outputSuccess();
    }

    /**
     * @page prettycommodity
     * @point 批量下架
     */
    public function shelfoffAction()
    {
        $params = $this->trimParams($this->request->getPost());
        ShelfValidation::make()->validators($params);
        $this->service->shelfoff($params);
        return $this->outputSuccess();
    }

    /**
     * @page prettycommodity
     * @point 明细
     */
    public function infoAction()
    {
        $params = $this->trimParams($this->request->get());
        InfoValidation::make()->validators($params);
        $res = $this->service->getInfo($params);
        return $this->outputSuccess($res);
    }
}
