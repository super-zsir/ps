<?php


namespace Imee\Controller\Audit;


use Imee\Controller\BaseController;
use Imee\Service\Domain\Service\Csms\CsmsTaskService;


/**
 * 内容安全管理 - 审核任务管理
 * Class CsmstaskController
 * @package Imee\Controller\Audit
 */
class CsmstaskController extends BaseController
{


	public $params;

	public function onConstruct()
	{
		parent::onConstruct();
		$get = $this->request->getQuery();
		$post = $this->request->getPost();
		$this->params = array_merge(
			['admin' => $this->uid],
			$get,
			$post
		);
	}


	/**
	 * @page csmstask
	 * @name 内容安全控制台-任务管理
	 * @point 任务列表
	 */
	public function listAction()
	{
		$service = new CsmsTaskService();
		$res = $service->list($this->params);
		return $this->outputSuccess($res['data'], ['total' => $res['total']]);
	}


	/**
	 * @page csmstask
	 * @point 任务配置
	 */
	public function configAction()
	{
		$service = new CsmsTaskService();
		$res = $service->config();
		return $this->outputSuccess($res);
	}

	/**
	 * @page csmstask
	 * @point 任务数设置
	 */
	public function numberAction()
	{
		$service = new CsmsTaskService();
		$res = $service->number($this->params);
		return $this->outputSuccess($res);
	}


	/**
	 * @page csmstask
	 * @point 员工列表
	 */
	public function stafflistAction()
	{
		$service = new CsmsTaskService();
		$res = $service->staffList($this->params);
		return $this->outputSuccess($res['data'], ['total' => $res['total']]);
	}

	/**
	 * @page csmstask
	 * @point 添加员工
	 */
	public function staffaddAction()
	{
		$service = new CsmsTaskService();
		$res = $service->staffAdd($this->params);
		return $this->outputSuccess($res);
	}

	/**
	 * @page csmstask
	 * @point 删除员工
	 */
	public function staffdelAction()
	{
		$service = new CsmsTaskService();
		$res = $service->staffdel($this->params);
		return $this->outputSuccess($res);
	}

	/**
	 * @page csmstask
	 * @point 全部员工
	 */
	public function staffallAction()
	{
		$service = new CsmsTaskService();
		$res = $service->staffAll();
		return $this->outputSuccess($res);
	}




}