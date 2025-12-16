<?php

namespace Imee\Service\Domain\Service\Audit\Processes\Csmsaudit;

use Imee\Helper\Constant\CsmsConstant;
use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xss\CsmsAudit;
use Imee\Models\Xss\CsmsChoice;
use Imee\Service\Domain\Service\Audit\ModuleChoiceService;
use Imee\Service\Domain\Service\Csms\Traits\CsmsTrait;
use Imee\Service\Helper;

class ConfigProcess
{
	use CsmsTrait;

	public function handle($params = [])
	{
		$format = [];

		foreach (Helper::getAllApp() as $k => $v) {
			$tmp = [];
			$tmp['label'] = $v;
			$tmp['value'] = (string)$k;
			$format['app'][] = $tmp;
		}

		foreach (CsmsAudit::state as $key => $value) {
			$tmp = [];
			$tmp['label'] = $value;
			$tmp['value'] = $key;
			$format['state'][] = $tmp;
		}


		$moduleChoiceService = new ModuleChoiceService();
		$moduleChoice = $moduleChoiceService->getModuleChoice([
			'module' => CsmsConstant::CSMS_AUDIT
		]);
		foreach ($moduleChoice as $key => $value) {
			$tmp = [];
			$tmp['label'] = $value['choice_name'];
			$tmp['value'] = $value['choice'];
			$format['table'][] = $tmp;
		}

		foreach ($this->sex as $key => $value) {
			$tmp = [];
			$tmp['label'] = $value;
			$tmp['value'] = $key;
			$format['sex'][] = $tmp;
		}


		// 类型模式审核，获取当前审核阶段、类型下的审核项
        if(isset($params['module']) && isset($params['type']) && $params['type']){
            $power = $this->getStaffPower($params['admin']);
            if($power){
                $choices = $power[$params['module']];
                $choiceInfo = CsmsChoice::find([
                    'conditions' => 'choice in ({choice:array})',
                    'bind' => [
                        'choice' => $choices
                    ]
                ])->toArray();
                if($choiceInfo){
                    foreach ($choiceInfo as $choice){
                        $format['choice'][] = [
                            'label' => $choice['choice_name'],
                            'value' => $choice['choice']
                        ];
                    }
                }
            }
        }
        // 所有语言 - 过滤后台用户权限
        foreach (Helper::getLanguageArr() as $language => $name){
            $format['language'][] = ['label' => $name, 'value' => $language];
        }


        // 地区
        foreach (XsBigarea::getBigAreaList() as $area => $areaName){
            $format['area'][] = ['label' => $areaName, 'value' => $area];
        }

        // 获取机审结果
        $format['machine'][] = ['label' => __T('全部', [], $params['lang']), 'value' => ''];
        $format['machine'][] = ['label' => __T('机审列表', [] ,$params['lang']), 'value' => CsmsAudit::MACHINE_PASS];
        $format['machine'][] = ['label' => __T('文本审核', [], $params['lang']), 'value' => CsmsAudit::MACHINE_REFUSE];
//        foreach (CsmsAudit::$machine_state as $mkey => $mvalue){
//            $format['machine'][] = ['label' => $mvalue, 'value' => $mkey];
//        }

		return $format;
	}


}