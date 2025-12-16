<?php


namespace Imee\Service\Domain\Service\Csms\Process\Csmsbench;


use Imee\Service\Domain\Service\Csms\CsmsBaseService;
use Imee\Service\Domain\Service\Csms\Process\CsmsBaseProcess;
use Imee\Service\Domain\Service\Csms\Traits\CsmsTrait;

class UserWorkModuleProcess extends CsmsBaseProcess
{
	use CsmsTrait;

	public function handle()
	{
		$admin = $this->context->admin;
		$power = $this->getStaffPower($admin, $this->context);

		$moduleChoice = $this->getModuleChoice();
		$moduleChoice = array_column($moduleChoice, null, 'module');
		$data = [];
		foreach ($power as $module => $choiceArr) {
			$one = [
				'module' => $module,
				'module_name' => isset($moduleChoice[$module]) ? $moduleChoice[$module]['module_name'] : ''
			];
			// 获取指定模块下的 待处理 和 已处理数
            $task_class = CsmsBaseService::getInstance($module);
//            $module_statistics = $task_class->moduleStatistics();
            $module_statistics = [];

			foreach ($choiceArr as $choiceKey => $choice) {
			    // 验证审核项 审核阶段是否下线
                if(!isset($moduleChoice[$module])) continue;
                if(!isset($moduleChoice[$module]['choice'])) continue;
                $checkModuleChoice = array_column($moduleChoice[$module]['choice'], null, 'choice');
                if(!isset($checkModuleChoice[$choice])) continue;

				$one['choice'] = $choice;
				$choices = $moduleChoice[$module]['choice'] ?? [];
				$choices = array_column($choices, null, 'choice');
                $one['choice_name'] = isset($choices[$choice]) ? $this->getChoiceNameByCid($choices[$choice]['cid']) : '';
				$one['undo'] = $module_statistics[$choice] ?? 0;
				$one['checked'] = $this->getCacheCount($module, $choice);

				$data[] = $one;
			}
		}
		$total = count($data);
		return ['data' => $data, 'total' => $total];
	}
}