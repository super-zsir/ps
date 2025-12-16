<?php

namespace Imee\Service\Domain\Service\Audit;

use Imee\Comp\Common\Redis\RedisBase;
use Imee\Models\Xss\CsmsModules;
use Imee\Models\Xss\CsmsModulesChoice;

/**
 * 审核模块-选项
 * Class ModuleChoiceService
 * @package Imee\Service\Domain\Service\Audit
 */
class ModuleChoiceService
{
    private static $module_choice_key = 'newkefu:modulechoice';

    public static function getAllModule()
    {
        $module = CsmsModules::find()->toArray();
        return $module;
    }

    public static function getAllChoice()
    {
        return CsmsModulesChoice::find()->toArray();
    }

    /**
     * 获取所有模块 和 审核项
     */
    public function moduleChoice()
    {
        $redis = new RedisBase(RedisBase::REDIS_ADMIN);
        $module_choice = $redis->get(self::$module_choice_key);
        if ($module_choice) {
            $module_choice = json_decode($module_choice, true);
        } else {
            $module = CsmsModules::find([
                'conditions' => 'state = :state:',
                'bind' => [
                    'state' => CsmsModules::STATUS_NORMAL
                ]
            ])->toArray();
            if (!$module) {
                return $module;
            }
            $module_ids = array_values(array_unique(array_column($module, 'mid')));
            $module_choice = array_column($module, null, 'mid');
            $choice = CsmsModulesChoice::find([
                'conditions' => 'mid in ({mid:array}) and state = :state:',
                'bind' => [
                    'mid' => $module_ids,
                    'state' => CsmsModulesChoice::STATUS_NORMAL
                ]
            ])->toArray();
            if (!$choice) {
                $module_choice = $module;
            } else {
                foreach ($choice as $key => $value) {
                    $module_choice[$value['mid']]['choice'][] = $value;
                }
                $module_choice = array_values($module_choice);
            }
            $redis->set(self::$module_choice_key, json_encode($module_choice));
            $redis->expire(self::$module_choice_key, 60 * 10);
        }
        return $module_choice;
    }


	/**
	 * 获取指定模块下的审核项
	 */
    public function getModuleChoice($params = [])
    {
    	$moduleChoice = [];
		$module = $params['module'] ?? '';
		if($module){
			$module = CsmsModules::findFirst([
				'conditions' => 'module = :module:',
				'bind' => [
					'module' => $module
				]
			]);
			if($module){
				$moduleChoice = CsmsModulesChoice::find([
					'conditions' => 'mid = :mid:',
					'bind' => [
						'mid' => $module->mid
					]
				])->toArray();
			}
		}
		return $moduleChoice;
    }
}
