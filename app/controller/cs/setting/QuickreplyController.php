<?php
namespace Imee\Controller\Cs\Setting;

use Imee\Controller\BaseController;
use Imee\Service\Domain\Service\Cs\Setting\QuickReplyService;

class QuickreplyController extends BaseController
{
	/**
	 * @page setting.quickreply
	 * @name 客服设置-快捷回复管理
	 * @point 列表
	 */
	public function indexAction()
	{
		$service = new QuickReplyService();
		$result = $service->getList($this->request->get());
		return $this->outputSuccess($result['data'], array('total' => $result['total']));
	}

	/**
	 * @page setting.quickreply
	 * @point 展示列表
	 */
	public function treeListAction()
	{
		$service = new QuickReplyService();
		return $this->outputSuccess($service->getTreeList());
	}

	/**
	 * @page setting.quickreply
	 * @point 创建
	 */
	public function createAction()
	{
		$service = new QuickReplyService();
		$service->create($this->request->getPost());
		return $this->outputSuccess();
	}

	/**
	 * @page setting.quickreply
	 * @point 修改
	 */
	public function modifyAction()
	{
		$service = new QuickReplyService();
		$service->modify($this->request->getPost());
		return $this->outputSuccess();
	}

	/**
	 * @page setting.quickreply
	 * @point 删除
	 */
	public function removeAction()
	{
		$service = new QuickReplyService();
		$service->del($this->request->getPost());
		return $this->outputSuccess();
	}

	/**
	 * @page setting.quickreply
	 * @point 组创建
	 */
	public function createGroupAction()
	{
		$service = new QuickReplyService();
		$service->createGroup($this->request->getPost());
		return $this->outputSuccess();
	}

	/**
	 * @page setting.quickreply
	 * @point 组修改
	 */
	public function modifyGroupAction()
	{
		$service = new QuickReplyService();
		$service->modifyGroup($this->request->getPost());
		return $this->outputSuccess();
	}

	/**
	 * @page setting.quickreply
	 * @point 组删除
	 */
	public function removeGroupAction()
	{
		$service = new QuickReplyService();
		$service->delGroup($this->request->getPost());
		return $this->outputSuccess();
	}

	/**
	 * @page setting.quickreply
	 * @point 组列表
	 */
	public function groupListAction()
	{
		$service = new QuickReplyService();
		$result = $service->getGroupList();
		return $this->outputSuccess($result['data'], array('total' => $result['total']));
	}

	/**
	 * @page setting.quickreply
	 * @point 配置
	 */
	public function configAction()
	{
		$service = new QuickReplyService();
		return $this->outputSuccess($service->config());
	}
}