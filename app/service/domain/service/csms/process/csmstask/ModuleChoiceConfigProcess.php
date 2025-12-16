<?php


namespace Imee\Service\Domain\Service\Csms\Process\Csmstask;


use Imee\Models\Xss\CsmsModules;
use Imee\Models\Xss\CsmsModulesChoice;

class ModuleChoiceConfigProcess
{


	public function handle()
	{
		$res = [];

		$module = CsmsModules::find([
			'conditions' => 'state = :state:',
			'bind' => [
				'state' => CsmsModules::STATUS_NORMAL
			]
		])->toArray();
		if (!$module) {
			return $module;
		}
		$moduleIds = array_values(array_unique(array_column($module, 'mid')));
		$moduleChoice = array_column($module, null, 'mid');
		$choice = CsmsModulesChoice::find([
			'conditions' => 'mid in ({mid:array}) and state = :state:',
			'bind' => [
				'mid' => $moduleIds,
				'state' => CsmsModulesChoice::STATUS_NORMAL
			]
		])->toArray();
		if (!$choice) {
			$moduleChoice = $module;
		} else {
			foreach ($choice as $key => $value) {
				$moduleChoice[$value['mid']]['choice'][] = $value;
			}
			$moduleChoice = array_values($moduleChoice);
		}

		if ($moduleChoice) {
			foreach ($moduleChoice as $module) {
				$one = [];

//				$one['module'] = $module['module'];
//				$one['module_name'] = $module['module_name'];

				$one['label'] = $module['module_name'];
				$one['value'] = $module['module'];
				if (isset($module['choice']) && $module['choice']) {
					foreach ($module['choice'] as $choice) {
						$one['choice'][] = ['label' => $choice['choice_name'], 'value' => $choice['choice']];
					}
				}
				$res['module'][] = $one;
			}
		}
		return $res;
	}

}