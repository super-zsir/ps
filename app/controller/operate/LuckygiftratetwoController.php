<?php

namespace Imee\Controller\Operate;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Luckygift\DynamicRateValidation;
use Imee\Controller\Validation\Luckygift\RateValidation;
use Imee\Service\Luckygift\RateService;

class LuckygiftratetwoController extends BaseController
{
	/**
	 * @var RateService $service
	 */
	private $service;

	protected function onConstruct()
	{
		parent::onConstruct();
		$this->service = new RateService;
		$this->params['proportion'] = 20;
	}

	/**
	 * @page luckygiftratetwo
	 * @name 幸运礼物玩法配置-20%分成预期
	 */
	public function mainAction()
	{
	}

	/**
	 * @page luckygiftratetwo
	 * @point 普通预期列表
	 */
	public function rateListAction()
	{
		if (!isset($this->params['property'])) {
			return $this->outputError('-1','属性必填');
		}
		$res = $this->service->getRateList($this->params);
		return $this->outputSuccess($res['data'], ['total' => $res['total']]);
	}

	/**
	 * @page luckygiftratetwo
	 * @point 普通预期添加
	 */
	public function rateCreateAction()
	{
		RateValidation::make()->validators($this->params);
		[$res, $msg] = $this->service->rateAdd($this->params);
		if (!$res) {
			return $this->outputError('-1', $msg);
		}
		return $this->outputSuccess();
	}

	/**
	 * @page luckygiftratetwo
	 * @point 普通预期修改
	 */
	public function rateModifyAction()
	{
		RateValidation::make()->validators($this->params);
		if (empty($this->params['id']) || $this->params['id'] < 1) {
			return $this->outputError('-1', 'id错误');
		}
		[$res, $msg] = $this->service->rateEdit($this->params);
		if (!$res) {
			return $this->outputError('-1', $msg);
		}
		return $this->outputSuccess();
	}

	/**
	 * @page luckygiftratetwo
	 * @point 普通预期删除
	 */
	public function rateDeleteAction()
	{
		if (empty($this->params['id']) || $this->params['id'] < 1) {
			return $this->outputError('-1', 'id错误');
		}
		[$res, $msg] = $this->service->rateDelete($this->params['id']);
		if (!$res) {
			return $this->outputError('-1', $msg);
		}
		return $this->outputSuccess();
	}

	/**
	 * @page luckygiftratetwo
	 * @point 普通预期详情
	 */
	public function rateInfoAction()
	{
		if (empty($this->params['id']) || $this->params['id'] < 1) {
			return $this->outputError('-1', 'id错误');
		}
		$res = $this->service->rateInfo($this->params['id']);
		return $this->outputSuccess($res);
	}

	/**
	 * @page luckygiftratetwo
	 * @point 动态预期列表
	 */
	public function dynamicRateListAction()
	{
		if (!isset($this->params['property'])) {
			return $this->outputError('-1','属性必填');
		}
		$res = $this->service->getDynamicRateList($this->params);
		return $this->outputSuccess($res['data'] ?? [], ['total' => $res['total'] ?? 0]);
	}

	/**
	 * @page luckygiftratetwo
	 * @point 动态预期添加
	 */
	public function dynamicRateCreateAction()
	{
		DynamicRateValidation::make()->validators($this->params);
		[$res, $msg] = $this->service->dynamicRateAdd($this->params);
		if (!$res) {
			return $this->outputError('-1', $msg);
		}
		return $this->outputSuccess();
	}

	/**
	 * @page luckygiftratetwo
	 * @point 动态预期修改
	 */
	public function dynamicRateModifyAction()
	{
		DynamicRateValidation::make()->validators($this->params);
		if (empty($this->params['id']) || $this->params['id'] < 1) {
			return $this->outputError('-1', 'id错误');
		}
		[$res, $msg] = $this->service->dynamicRateEdit($this->params);
		if (!$res) {
			return $this->outputError('-1', $msg);
		}
		return $this->outputSuccess();
	}

	/**
	 * @page luckygiftratetwo
	 * @point 动态预期删除
	 */
	public function dynamicRateDeleteAction()
	{
		if (empty($this->params['id']) || $this->params['id'] < 1) {
			return $this->outputError('-1', 'id错误');
		}
		[$res, $msg] = $this->service->dynamicRateDelete($this->params['id']);
		if (!$res) {
			return $this->outputError('-1', $msg);
		}
		return $this->outputSuccess();
	}

	/**
	 * @page luckygiftratetwo
	 * @point 动态预期详情
	 */
	public function dynamicRateInfoAction()
	{
		if (empty($this->params['id']) || $this->params['id'] < 1) {
			return $this->outputError('-1', 'id错误');
		}
		$res = $this->service->dynamicRateInfo($this->params['id']);
		return $this->outputSuccess($res);
	}
}