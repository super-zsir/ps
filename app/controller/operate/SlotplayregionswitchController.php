<?php

namespace Imee\Controller\Operate;

use Imee\Controller\BaseController;
use Imee\Service\Slot\RegionSwitchService;

class SlotplayregionswitchController extends BaseController
{
    /** @var RegionSwitchService $service */
    private $service;

	protected function onConstruct()
	{
		parent::onConstruct();
		$this->service = new RegionSwitchService();
	}

	/**
	 * @page slotplayregionswitch
	 * @name Slot游戏玩法配置-Slot游戏大区开关
	 */
	public function mainAction()
	{
	}

	/**
	 * @page slotplayregionswitch
	 * @point 列表
	 */
	public function listAction()
	{
		$res = $this->service->getList($this->params, 'id desc', 1, 20);
		return $this->outputSuccess($res['data'] ?? [], ['total' => $res['total'] ?? 0]);
	}

	/**
	 * @page  slotplayregionswitch
	 * @point 修改
	 */
	public function modifyAction()
	{
		[$result, $msg] = $this->service->modify($this->params['bigarea_id'], $this->params['switch'], $this->params['global_rank_switch']);
		if (!$result) {
			return $this->outputError('-1', $msg);
		}
		return $this->outputSuccess($result);
	}
}