<?php

namespace Imee\Controller\Operate;

use Imee\Comp\Common\Export\Service\ExportService;
use Imee\Controller\BaseController;
use Imee\Export\LuckyGiftGlobalExport;
use Imee\Service\Luckygift\GlobalService;

class LuckygiftglobalController extends BaseController
{
	/** @var  GlobalService $service */
	private $service;

	protected function onConstruct()
	{
		parent::onConstruct();
		$this->service = new GlobalService();
	}

	/**
	 * @page luckygiftglobal
	 * @name 幸运礼物玩法配置-全局数据查询
	 */
	public function mainAction()
	{
	}

	/**
	 * @page luckygiftglobal
	 * @point 列表
	 */
	public function listAction()
	{
		$data = $this->service->getList($this->params);
		return $this->outputSuccess($data['data'] ?? [], ['total' => $data['total'] ?? 0]);
	}

	/**
	 * @page luckygiftglobal
	 * @point 创建
	 */
	public function createAction()
	{
		return $this->outputSuccess([]);
	}

	/**
	 * @page luckygiftglobal
	 * @point 编辑
	 */
	public function modifyAction()
	{
		return $this->outputSuccess([]);
	}

	/**
	 * @page luckygiftglobal
	 * @point 删除
	 */
	public function deleteAction()
	{
		return $this->outputSuccess([]);
	}

    /**
     * @page luckygiftglobal
     * @point 导出
     */
    public function exportAction()
    {
        $this->params['guid'] = 'luckygiftglobal';
        ExportService::addTask($this->uid, 'luckygiftglobal.xlsx', [LuckyGiftGlobalExport::class, 'export'], $this->params, '幸运礼物全局查询导出');
        ExportService::showHtml();

        return $this->outputSuccess();
    }
}