<?php

namespace Imee\Service\Domain\Service\Csms\Process\Staff;

use Imee\Helper\Constant\CsmsConstant;
use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xss\CsmsChoice;
use Imee\Models\Xss\CsmsModules;
use Imee\Models\Xss\CsmsStaff;
use Imee\Models\Xss\CsmsUserChoice;
use Imee\Service\Domain\Service\Abstracts\NormalListAbstract;
use Imee\Service\Domain\Service\Csms\Context\Staff\StaffListContext;
use Imee\Service\Domain\Service\Csms\Traits\CsmsTrait;
use Imee\Service\Domain\Service\Traits\UserInfoTrait;
use Imee\Service\Helper;

class StaffListProcess extends NormalListAbstract
{

	use UserInfoTrait;
	use CsmsTrait;

	public function __construct(StaffListContext $context)
	{
		$this->context = $context;
		$this->masterClass = CsmsStaff::class;
		$this->query = CsmsStaff::query();
	}



	protected function buildWhere()
	{
		// TODO: Implement buildWhere() method.
		$this->where['condition'][] = 'state = :state:';
		$this->where['bind']['state'] = CsmsConstant::STATE_NORMAL;

        if($this->context->userId){
            $this->where['condition'][] = 'user_id = :user_id:';
            $this->where['bind']['user_id'] = $this->context->userId;
        }
	}


	protected function formatList($items)
	{
		// TODO: Implement formatList() method.
		if($items){
			$items = is_array($items) ? $items : $items->toArray();

			$userUids = array_column($items, 'user_id');
			$staffUserInfoMap = $this->getStaffBaseInfos($userUids);

			$userChoiceMap = $this->getChoiceMap($userUids);

			foreach ($items as &$item){
				$item['user_name'] = isset($staffUserInfoMap[$item['user_id']]['user_name']) ? $staffUserInfoMap[$item['user_id']]['user_name'] : '';

				$appStr = $item['app_id'];
				$item['app_ids'] = explode(',', $appStr);
				$appNameList = array_map(function ($v) {
					return $this->getAppName($v);
				}, $item['app_ids']);
				$item['app_names'] = implode(',', $appNameList);

				$areas = $item['area'];
				$item['areas'] = explode(',', $areas);
				$areaNameList = array_map(function ($v){
				    return XsBigarea::getBigAreaCnName($v);
                }, $item['areas']);
				$item['area_names'] = implode(',', $areaNameList);

				$value['power'] = '';
				if (isset($userChoiceMap[$item['user_id']])) {
					$item['power'] = implode('<br />', $userChoiceMap[$item['user_id']]['power']);
					$item['choices'] = $userChoiceMap[$item['user_id']]['choices'];
				}
				$item['manager'] = (string)$item['manager'];
				$item['manager_name'] = CsmsStaff::$manager[$item['manager']];

			}

		}
		return $items;
	}


	private function getChoiceMap($opUids)
	{
		$userChoiceMap = [];

		$userChoiceList = CsmsUserChoice::find([
			'conditions' => 'state = :state: and user_id in({user_ids:array})',
			'bind' => [
				'state' => CsmsUserChoice::STATUS_NORMAL,
				'user_ids' => $opUids,
			]
		])->toArray();

		if (empty($userChoiceList)) {
			return $userChoiceMap;
		}

		$moduleMap = $choiceMap = [];
		$moduleIds = $choiceIds = [];
		foreach ($userChoiceList as $v) {
			$moduleIds[] = $v['module_id'];
			$choiceIds[] = $v['choice_id'];
		}

		$moduleList = CsmsModules::find([
			'conditions' => 'mid in({mids:array})',
			'bind' => [
				'mids' => $moduleIds,
			]
		])->toArray();
		$moduleMap = array_column($moduleList, null, 'mid');

		$choiceList = CsmsChoice::find([
			'conditions' => 'id in({cids:array})',
			'bind' => [
				'cids' => $choiceIds,
			]
		])->toArray();
		$choiceMap = array_column($choiceList, null, 'id');

		foreach ($userChoiceList as $v) {
			$choiceName = isset($choiceMap[$v['choice_id']]) ? $choiceMap[$v['choice_id']]['choice_name'] : '';
			$moduleName = isset($moduleMap[$v['module_id']]) ? $moduleMap[$v['module_id']]['module_name'] : '';
			$userChoiceMap[$v['user_id']]['power'][] = $choiceName . ' ('. $moduleName . ')';
			$userChoiceMap[$v['user_id']]['choices'][] = $moduleMap[$v['module_id']]['module'].'-'.$choiceMap[$v['choice_id']]['choice'];
		}

		return $userChoiceMap;
	}


}