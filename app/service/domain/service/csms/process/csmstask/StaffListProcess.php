<?php


namespace Imee\Service\Domain\Service\Csms\Process\Csmstask;


use Imee\Models\Xss\CsmsUserChoice;
use Imee\Service\Domain\Service\Abstracts\NormalListAbstract;
use Imee\Service\Domain\Service\Traits\UserInfoTrait;

class StaffListProcess extends NormalListAbstract
{

	use UserInfoTrait;

	public function __construct($context)
	{
		$this->context = $context;
		$this->masterClass = CsmsUserChoice::class;
		$this->query = CsmsUserChoice::query();
	}


	public function buildWhere()
	{
		// TODO: Implement buildWhere() method.
		$this->where['condition'][] = 'state = :state:';
		$this->where['bind']['state'] = CsmsUserChoice::STATUS_NORMAL;

		if ($this->context->module) {
			$this->where['condition'][] = 'module = :module:';
			$this->where['bind']['module'] = $this->context->module;
		}
		if ($this->context->choice) {
			$this->where['condition'][] = 'choice = :choice:';
			$this->where['bind']['choice'] = $this->context->choice;
		}
	}



	public function formatList($items)
	{
		// TODO: Implement formatList() method.
		if ($items) {
			$items = $items->toArray();
			$userIds = array_values(array_unique(array_column($items, 'user_id')));
			$userInfo = $this->getStaffBaseInfos($userIds);

			foreach ($items as $key => &$value) {
				$value['user_name'] = isset($userInfo[$value['user_id']]) ? $userInfo[$value['user_id']]['user_name'] : '';
				$value['create_time'] = date("Y-m-d H:i:s", $value['create_time']);
				$value['update_time'] = date("Y-m-d H:i:s", $value['update_time']);
			}
		}
		return $items;
	}



}