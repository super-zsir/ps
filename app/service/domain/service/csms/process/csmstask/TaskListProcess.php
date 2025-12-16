<?php


namespace Imee\Service\Domain\Service\Csms\Process\Csmstask;


use Imee\Helper\Constant\CsmsConstant;
use Imee\Models\Xss\CsmsTasklimit;
use Imee\Models\Xss\CsmsUserChoice;
use Imee\Service\Domain\Service\Audit\Traits\AuditTrait;
use Imee\Service\Domain\Service\Csms\Context\Csmstask\TaskListContext;
use Imee\Service\Domain\Service\Csms\CsmsBaseService;
use Imee\Service\Domain\Service\Csms\CsmsTaskService;
use Imee\Service\Domain\Service\Csms\Traits\CsmsTrait;
use Imee\Service\Domain\Service\Traits\UserInfoTrait;

class TaskListProcess
{
	use UserInfoTrait;
	use AuditTrait;
    use CsmsTrait;


	public function __construct(TaskListContext $context)
	{
		$this->context = $context;
	}



	public function handle()
	{
		$module = $this->context->module;
		$choice = $this->context->choice;

		$auditTaskService = new CsmsTaskService();
		$moduleChoice = $auditTaskService->moduleChoice();

		// 获取所有任务设置
		$taskNumber = CsmsTasklimit::find()->toArray();
		$taskLimit = [];
		if ($taskNumber) {
			foreach ($taskNumber as $numberLimit) {
				$taskLimit[$numberLimit['module'] . '-' . $numberLimit['choice']] = $numberLimit['number'];
			}
		}

		$data = [];


		foreach ($moduleChoice as $moduleKey => $moduleValue) {
			$moduleInfo = $moduleValue;
			$moduleOneName = $moduleValue['module'];
			if ($module && $module != $moduleOneName) {
				continue;
			}

			$moduleChoices = $moduleValue['choice'] ?? [];
			$choices = [];
			if ($choice) {
				foreach ($moduleChoices as $c_key => $c_value) {
					if ($choice && $c_value['choice'] == $choice) {
						$choices = [$c_value];
					}
				}
			} else {
				$choices = $moduleChoices;
			}


			if (!$choices) {
				continue;
			}

			// TODO-TORCH
			// 获取指定模块下的 选项待处理和已处理数
			$task_class = CsmsBaseService::getInstance($moduleInfo['module']);
//			$module_statistics = $task_class->moduleStatistics();
            $module_statistics = [];


			foreach ($choices as $choiceKey => $choiceValue) {
				$one = [
					'module' => $moduleInfo['module'],
					'module_name' => $moduleInfo['module_name'],
					'choice' => $choiceValue['choice'],
					'choice_name' => $this->getChoiceNameByCid($choiceValue['cid'])
				];

				// 获取审核项下的员工
				$staff = CsmsUserChoice::find([
					'conditions' => 'module = :module: and choice = :choice: and state = :state:',
					'bind' => [
						'module' => $moduleInfo['module'],
						'choice' => $choiceValue['choice'],
						'state' => CsmsUserChoice::STATUS_NORMAL
					]
				])->toArray();

				if (!$staff) {
					$one['staff'] = $staff;
				} else {
					$user_ids = array_values(array_unique(array_column($staff, 'user_id')));
					$users = $this->getStaffBaseInfos($user_ids);
					$staff_names = '';
					foreach ($staff as $staff_key => $staff_value) {
						$one['staff'][] = [
							'user_id' => isset($users[$staff_value['user_id']]) ? $users[$staff_value['user_id']]['user_id'] : '',
							'user_name' => isset($users[$staff_value['user_id']]) ? $users[$staff_value['user_id']]['user_name'] : ''
						];
						$staff_names .= (isset($users[$staff_value['user_id']]) ? $users[$staff_value['user_id']]['user_name'] : '');
					}
					$one['staff_names'] = implode(',', array_column($one['staff'], 'user_name'));
				}


//				// 已处理，未处理
				$one['undo'] = $module_statistics[$choiceValue['choice']] ?? 0;
				$one['checked'] = $this->getCacheCount($moduleInfo['module'], $choiceValue['choice']);
//
//				// 任务数
				$one['number'] = $taskLimit[$moduleInfo['module'] . '-' . $choiceValue['choice']] ?? CsmsConstant::TASK_DEFAULT_NUMBER;

				$data[] = $one;
			}

			$total = count($data);
		}
		return [
			'data' => $data,
			'total' => $total ?? 0
		];
	}

}