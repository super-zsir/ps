<?php

namespace Imee\Service\Domain\Service\Csms\Process\Csmsbench;

use Imee\Comp\Common\Redis\RedisBase;
use Imee\Helper\Constant\CsmsConstant;
use Imee\Service\Domain\Service\Csms\Context\Csmsbench\ClearTaskContext;

class ClearTaskProcess
{

	protected $context;

	public function __construct(ClearTaskContext $context)
	{
		$this->context = $context;
	}

	public function handle()
	{
		$admin = $this->context->admin;
		$module = $this->context->module;
		$choice = $this->context->choice;

		$redis = new RedisBase(RedisBase::REDIS_ADMIN);
		$module_user_key = CsmsConstant::REDIS_MODULE_USER.$module.'-'.$choice;
		$redis->Srem($module_user_key, $admin);
		$redis_task_key = CsmsConstant::REDIS_STAFF_TASK_PRE.$module.'-'.$choice;
		$redis->delete($redis_task_key.'-'.$admin);
		return true;
	}


}