<?php


namespace Imee\Service\Domain\Service\Csms;


use Imee\Service\Domain\Service\Csms\Context\Staff\StaffAddContext;
use Imee\Service\Domain\Service\Csms\Context\Staff\StaffDelContext;
use Imee\Service\Domain\Service\Csms\Context\Staff\StaffEditContext;
use Imee\Service\Domain\Service\Csms\Context\Staff\StaffListContext;
use Imee\Service\Domain\Service\Csms\Process\Staff\StaffDelProcess;
use Imee\Service\Domain\Service\Csms\Process\Staff\StaffAddProcess;
use Imee\Service\Domain\Service\Csms\Process\Staff\StaffConfigProcess;
use Imee\Service\Domain\Service\Csms\Process\Staff\StaffEditProcess;
use Imee\Service\Domain\Service\Csms\Process\Staff\StaffListProcess;

/**
 * 员工管理
 * Class StaffService
 * @package Imee\Service\Domain\Service\Csms
 */
class StaffService
{


	/**
	 * 员工列表
	 * @param array $params
	 * @return mixed
	 */
	public function staffList($params = [])
	{
		$context = new StaffListContext($params);
		$process = new StaffListProcess($context);
		return $process->handle();
	}

	/**
	 * 员工配置
	 * @return array
	 */
	public function staffConfig()
	{
		$process = new StaffConfigProcess();
		return $process->handle();
	}

	/**
	 * 添加员工
	 * @param array $params
	 */
	public function staffAdd($params = [])
	{
		$context = new StaffAddContext($params);
		$process = new StaffAddProcess($context);
		return $process->handle();
	}


	/**
	 * 编辑员工
	 * @param array $params
	 */
	public function staffEdit($params = [])
	{
		$context = new StaffEditContext($params);
		$process = new StaffEditProcess($context);
		return $process->handle();
	}


	/**
	 * 删除员工
	 */
	public function staffDel($params = [])
	{
		$context = new StaffDelContext($params);
		$process = new StaffDelProcess($context);
		return $process->handle();
	}



}