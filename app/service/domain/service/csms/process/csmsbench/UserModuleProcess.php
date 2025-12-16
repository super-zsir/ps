<?php

namespace Imee\Service\Domain\Service\Csms\Process\Csmsbench;

use Imee\Service\Domain\Service\Csms\Traits\CsmsTrait;

class UserModuleProcess
{

	use CsmsTrait;

	protected $context;

	public function __construct($context)
	{
		$this->context = $context;
	}

	public function handle()
	{
		$admin = $this->context->admin;
		// 工作台审核模块列表
		$power = $this->getStaffPower($admin);

		$modules = $this->getAllModule();
		$choices = $this->getAllChoice();
		$modules = array_column($modules, null, 'module');
		$choices = array_column($choices, null, 'choice');
		$data = [];

		foreach ($power as $module => $user_choice) {
			$one = [];
			$one['value'] = $module;
			$one['label'] = isset($modules[$module]) ? $modules[$module]['module_name'] : '';
			foreach ($user_choice as $choice) {
				$one['choice'][] = [
					'value' => $choice,
					'label' => isset($choices[$choice]) ? $choices[$choice]['choice_name'] : ''
				];
			}
			$data[] = $one;
		}
		return $data;
	}
}