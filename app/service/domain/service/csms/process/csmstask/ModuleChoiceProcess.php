<?php

namespace Imee\Service\Domain\Service\Csms\Process\Csmstask;

use Imee\Models\Bms\XsstKefuModules;
use Imee\Models\Bms\XsstKefuModulesChoice;
use Imee\Models\Xss\CsmsModules;
use Imee\Models\Xss\CsmsModulesChoice;

class ModuleChoiceProcess
{
	public function handle()
	{
		$res = [];
//		$redis = new RedisBase(RedisBase::REDIS_H5);
//		$moduleChoice = $redis->get(AuditConstant::MODULE_CHOICE_KEY);
//		if ($moduleChoice) {
//			$moduleChoice = json_decode($moduleChoice, true);
//		} else {
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
//			$redis->set(AuditConstant::MODULE_CHOICE_KEY, json_encode($moduleChoice));
//			$redis->expire(AuditConstant::MODULE_CHOICE_KEY, 60 * 10);
//		}
		return $moduleChoice;
	}


}