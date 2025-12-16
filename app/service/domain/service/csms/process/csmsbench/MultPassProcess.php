<?php


namespace Imee\Service\Domain\Service\Csms\Process\Csmsbench;


use Imee\Service\Domain\Service\Csms\CsmsBaseService;
use Imee\Service\Domain\Service\Csms\Exception\CsmsWorkbenchException;
use Imee\Service\Domain\Service\Csms\Traits\CsmsTrait;

class MultPassProcess
{

	use CsmsTrait;

	protected $params;

	public function __construct($params = [])
	{
		$this->params = $params;
	}

	public function handle()
	{
		$module = isset($this->params['module']) ? $this->params['module'] : '';
		$admin = isset($this->params['admin']) ? $this->params['admin'] : '';
		// 新系统默认验证权限，但是如果是主管，直接查看所有
		$isLeader = $this->isLeaderPurview();

		if(!$isLeader){
			$power = $this->getStaffPower($admin);
			if (!isset($power[$module])) {
				CsmsWorkbenchException::throwException(CsmsWorkbenchException::STAFF_MODULE_POWER_NOTEXIST);
			}
			if (!$power[$module]) {
				CsmsWorkbenchException::throwException(CsmsWorkbenchException::STAFF_MODULE_CHOICE_NOTEXIST);
			}
		}
		$task_class = CsmsBaseService::getInstance($module);
		return $task_class->multpass($this->params);
	}

}