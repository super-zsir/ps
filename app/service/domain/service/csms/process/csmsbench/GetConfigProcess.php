<?php

namespace Imee\Service\Domain\Service\Csms\Process\Csmsbench;

use Imee\Service\Domain\Service\Csms\CsmsBaseService;
use Imee\Service\Domain\Service\Csms\Exception\CsmsWorkbenchException;
use Imee\Service\Domain\Service\Csms\Traits\CsmsTrait;

class GetConfigProcess
{
	use CsmsTrait;

	public function __construct($params = [])
	{
		$this->params = $params;
	}

	public function handle()
	{
		$admin = $this->params['admin'] ?? 0;
		$module = $this->params['module'] ?? '';

		if (!$module) {
			CsmsWorkbenchException::throwException(CsmsWorkbenchException::TASKLIST_PARAM_ERROR);
		}

		$power = $this->getStaffPower($admin);
		if (!isset($power[$module])) {
			CsmsWorkbenchException::throwException(CsmsWorkbenchException::STAFF_MODULE_POWER_NOTEXIST);
		}
		$taskClass = CsmsBaseService::getInstance($module);
		return $taskClass->getConfig($this->params);
	}
}