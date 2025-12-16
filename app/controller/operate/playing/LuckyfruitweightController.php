<?php

namespace Imee\Controller\Operate\Playing;

use Imee\Controller\BaseController;
use Imee\Service\Operate\Play\Luckyfruit\WeightService;

class LuckyfruitweightController extends BaseController
{
    /** @var WeightService $service */
    private $service;

    protected function onConstruct()
    {
        $this->allowSort = ['id'];
        parent::onConstruct();
        $this->service = new WeightService();
    }

    /**
     * @page luckyfruitweight
     * @name Lucky Fruit Weights Config
     */
    public function mainAction()
    {
    }

    /**
     * @page luckyfruitweight
     * @point 列表
     */
    public function listAction()
    {
        $tabId = $this->params['tab_id'] ?? 0;
        $res = $this->service->getList($tabId);
        return $this->outputSuccess($res);
    }

    /**
     * @page luckyfruitweight
     * @point 修改
     * @logRecord(content = "修改", action = "0", model = "luckyfruitweight", model_id = "id")
     */
    public function modifyAction()
    {
        $data = $this->service->modify($this->params);
        return $this->outputSuccess($data);
    }

    /**
     * @page luckyfruitweight
     * @point tab列表
     */
    public function tabListAction()
    {
        $res = $this->service->getTabList();
        return $this->outputSuccess($res);
    }

    /**
     * @page luckyfruitweight
     * @point 创建tab
     * @logRecord(content = "创建tab", action = "0", model = "luckyfruitweighttab", model_id = "tab_id")
     */
    public function createTabAction()
    {
        $data = $this->service->createTab();
        return $this->outputSuccess($data);
    }

    /**
     * @page luckyfruitweight
     * @point 修改tab
     * @logRecord(content = "修改tab名称", action = "1", model = "luckyfruitweighttab", model_id = "tab_id")
     */
    public function modifyTabAction()
    {
        $data = $this->service->modifyTab($this->params);
        return $this->outputSuccess($data);
    }

    /**
     * @page luckyfruitweight
     * @point 删除tab
     * @logRecord(content = "删除tab", action = "2", model = "luckyfruitweighttab", model_id = "tab_id")
     */
    public function deleteTabAction()
    {
        $tabId = $this->params['tab_id'] ?? 0;
        $this->service->deleteTab($tabId);
        return $this->outputSuccess(['tab_id' => $tabId, 'after_json' => []]);
    }

    /**
     * @page luckyfruitweight
     * @point 总编辑
     */
    public function modifyTotalAction()
    {
        $data = $this->service->modifyTotal($this->params);
        return $this->outputSuccess($data);
    }
}