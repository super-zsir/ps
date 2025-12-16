<?php


namespace Imee\Service\Domain\Service\Csms;


use Imee\Service\Domain\Service\Csms\Context\Csmstask\TaskListContext;
use Imee\Service\Domain\Service\Csms\Context\Csmstask\TaskNumberContext;
use Imee\Service\Domain\Service\Csms\Process\Csmstask\ModuleChoiceConfigProcess;
use Imee\Service\Domain\Service\Csms\Process\Csmstask\ModuleChoiceProcess;
use Imee\Service\Domain\Service\Csms\Process\Csmstask\StaffAddProcess;
use Imee\Service\Domain\Service\Csms\Process\Csmstask\StaffAllProcess;
use Imee\Service\Domain\Service\Csms\Process\Csmstask\StaffDelProcess;
use Imee\Service\Domain\Service\Csms\Process\Csmstask\StaffListProcess;
use Imee\Service\Domain\Service\Csms\Process\Csmstask\TaskListProcess;
use Imee\Service\Domain\Service\Csms\Process\Csmstask\TaskNumberProcess;

/**
 * 内容安全 - 审核任务管理
 * Class CsmsTaskService
 * @package Imee\Service\Domain\Service\Csms
 */
class CsmsTaskService
{
	public function config()
	{
		$process = new ModuleChoiceConfigProcess();
		return $process->handle();
	}


	public function moduleChoice()
	{
		$process = new ModuleChoiceProcess();
		return $process->handle();
	}


	public function list($params = [])
	{
		$context = new TaskListContext($params);
		$process = new TaskListProcess($context);
		return $process->handle();
	}

	/**
	 * 任务数设置
	 * @param array $params
	 * @return bool
	 */
	public function number($params = [])
	{
		$context = new TaskNumberContext($params);
		$process = new TaskNumberProcess($context);
		return $process->handle();
	}


	public function staffList($params = [])
	{
		$context = new TaskListContext($params);
		$process = new StaffListProcess($context);
		return $process->handle();
	}

	public function staffAdd($params = [])
	{
		$context = new TaskListContext($params);
		$process = new StaffAddProcess($context);
		return $process->handle();
	}

	public function staffdel($params = [])
	{
		$context = new TaskListContext($params);
		$process = new StaffDelProcess($context);
		return $process->handle();
	}

	public function staffAll()
	{
		$process = new StaffAllProcess();
		return $process->handle();
	}
}