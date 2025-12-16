<?php

namespace Imee\Controller\Operate;

use Imee\Controller\BaseController;
use Imee\Service\Luckygift\RegionSwitchService;

class LuckygiftregionswitchController extends BaseController
{
	/** @var RegionSwitchService $service */
	private $service;

	protected function onConstruct()
	{
		parent::onConstruct();
		$this->service = new RegionSwitchService();
	}

	/**
	 * @page luckygiftregionswitch
	 * @name 幸运礼物玩法配置-幸运礼物大区开关
	 */
	public function mainAction()
	{
	}

	/**
	 * @page luckygiftregionswitch
	 * @point 列表
	 */
	public function listAction()
	{
		$res = $this->service->getList($this->params, 1, 20);
		return $this->outputSuccess($res['data'] ?? [], ['total' => $res['total'] ?? 0]);
	}

	/**
	 * @page  luckygiftregionswitch
	 * @point 修改
	 */
	public function modifyAction()
	{
		[$result, $msg] = $this->service->modify($this->params['id'], $this->params['lucky_gift_switch']);
		if (!$result) {
			return $this->outputError('-1', $msg);
		}
		return $this->outputSuccess($result);
	}
}