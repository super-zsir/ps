<?php


namespace Imee\Controller\Audit;


use Imee\Controller\BaseController;
use Imee\Service\Domain\Service\Csms\StaffService;

/**
 * 员工管理
 * Class CsmsStaffController
 * @package Imee\Controller\Saas
 */
class CsmsstaffController extends BaseController
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
	 * @page csmsstaff
	 * @name 内容安全控制台-审核安排
	 * @point 员工列表
	 */
	public function stafflistAction()
	{
		$service = new StaffService();
		$res = $service->staffList($this->params);
		return $this->outputSuccess($res['data'], ['total' => $res['total']]);
	}


	/**
	 * @page csmsstaff
	 * @point 员工配置
	 */
	public function staffconfigAction()
	{
		$service = new StaffService();
		$res = $service->staffConfig();
		return $this->outputSuccess($res);
	}


	/**
	 * @page csmsstaff
	 * @point 添加员工
	 */
	public function staffaddAction()
	{
		$service = new StaffService();
		$res = $service->staffAdd($this->params);
		return $this->outputSuccess($res);

	}


	/**
	 * @page csmsstaff
	 * @point 删除员工
	 */
	public function staffdelAction()
	{
		$service = new StaffService();
		$res = $service->staffDel($this->params);
		return $this->outputSuccess($res);
	}


	/**
	 * @page csmsstaff
	 * @point 编辑员工
	 */
	public function staffeditAction()
	{
		$service = new StaffService();
		$res = $service->staffEdit($this->params);
		return $this->outputSuccess($res);
	}
}