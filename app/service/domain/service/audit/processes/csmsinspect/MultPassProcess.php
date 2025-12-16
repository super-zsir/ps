<?php

namespace Imee\Service\Domain\Service\Audit\Processes\Csmsinspect;

use Imee\Comp\Common\Redis\RedisBase;
use Imee\Helper\Constant\CsmsConstant;
use Imee\Models\Xss\CsmsAudit;
use Imee\Service\Domain\Service\Csms\Context\Staff\TextMachineMultpassContext;
use Imee\Service\Domain\Service\Csms\Exception\CsmsWorkbenchException;
use Imee\Service\Domain\Service\Csms\Traits\CsmsTrait;

class MultPassProcess
{

	use CsmsTrait;
	protected $context;
	protected $power = 'old';

	public function __construct(TextMachineMultpassContext $context)
	{
		$this->context = $context;
		// 如果是主管 走循环清理，如果是审核人员，清理自己的
		$this->power = $this->isLeaderPurview() ? 'old' : 'new';
	}

	public function handle()
	{
		return $this->newMultpass();
	}

	private function newMultpass()
	{
		$module = isset($this->context->module) ? $this->context->module : '';
		$choice = isset($this->context->choice) ? $this->context->choice : '';
		$deleted = $this->context->deleted;
		$ids = $this->context->ids;
		$admin = $this->context->admin;

		$now = time();
        $all_ids = is_array($ids) ? $ids : explode(',', $ids);


		if ($this->power == 'new') {
			$redis = new RedisBase(RedisBase::REDIS_ADMIN);
			$redis_key = CsmsConstant::REDIS_STAFF_TASK_PRE . $module . '-' . $choice;
			$check_task = $this->checkTaskTimeout($module, $choice, $admin, $all_ids);
			if (!$check_task) {
				CsmsWorkbenchException::throwException(CsmsWorkbenchException::TASK_TIME_OUT);
			}
		}


		$dirtys = CsmsAudit::find([
			'conditions' => 'id in ({ids:array})',
			'bind' => ['ids' => $all_ids]
		]);
		foreach ($dirtys as $dirty) {
            $cache_list = [];
            $cache_data = [];
			// 已经审核过
			if(in_array($dirty->deleted3, CsmsConstant::$allow_state)) continue;

			$dirty->deleted3 = $deleted;
			$dirty->op3 = $admin;
			$dirty->op_dateline3 = $now;
			$dirty->save();
			$cache_list[] = $dirty->toArray();

			if ($this->power == 'old') {
				$this->oldDeleteRedisTask(CsmsConstant::CSMS_INSPECT, $dirty->choice, $dirty->id);
			}


			if ($this->power == 'new') {
				if ($cache_list) {
					foreach ($cache_list as $cache_key => $cache_value) {
						$redis->sRem($redis_key.'-'.$admin, $cache_value['id']);
						$cache_data[$cache_value['id']] = $cache_value['choice'];
					}
					$this->changeCacheCount(CsmsConstant::CSMS_INSPECT, $cache_data);
				}
			}
		}
		return true;
	}

}