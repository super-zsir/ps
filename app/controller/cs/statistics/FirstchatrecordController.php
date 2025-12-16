<?php

namespace Imee\Controller\Cs\Statistics;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Cs\Statistics\FirstChatRecord\ListValidation;
use Imee\Service\Domain\Service\Cs\Statistics\FirstChatRecordService;

class FirstchatrecordController extends BaseController
{
	/**
	 * @page statistics.firstChatRecord
	 * @name 客服系统-客服业务数据-首次会话记录管理
	 * @point 列表
	 */
	public function indexAction()
	{
		ListValidation::make()->validators($this->request->get());
		$service = new FirstChatRecordService();
		$result = $service->list($this->request->get());
		return $this->outputSuccess($result['data'], array('total' => $result['total']));
	}

	/**
	 * @page statistics.firstChatRecord
	 * @point 配置
	 */
	public function configAction()
	{
		$service = new FirstChatRecordService();
		return $this->outputSuccess($service->config());
	}
}