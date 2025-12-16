<?php


namespace Imee\Service\Domain\Service\Csms\Process\Csmstask;


use Imee\Exception\Audit\AuditTaskException;
use Imee\Helper\Constant\AuditConstant;
use Imee\Helper\Constant\CsmsConstant;
use Imee\Models\Bms\XsstKefuModules;
use Imee\Models\Bms\XsstKefuModulesChoice;
use Imee\Models\Bms\XsstKefuUserChoice;
use Imee\Models\Xss\CsmsModules;
use Imee\Models\Xss\CsmsModulesChoice;
use Imee\Models\Xss\CsmsUserChoice;
use Imee\Service\Domain\Service\Csms\Exception\CsmsStaffException;
use Imee\Service\Domain\Service\Traits\UserInfoTrait;

class StaffAddProcess
{

	use UserInfoTrait;

	public function __construct($context)
	{
		$this->context = $context;
	}

	public function handle()
	{


		$module = $this->context->module;
		$choice = $this->context->choice;
		$user_id = $this->context->userId;

		$users = $this->getStaffBaseInfos([$user_id]);

		if (!$users || !isset($users[$user_id])) {
			CsmsStaffException::throwException(CsmsStaffException::USER_NOT_EXIST);
		}

		$user = $users[$user_id];
		if ($user['user_status'] != AuditConstant::STATUS_NORMAL) {
			CsmsStaffException::throwException(CsmsStaffException::USER_NOT_NORMAL);
		}
		$userChoice = CsmsUserChoice::findFirst([
			'conditions' => 'user_id = :user_id: and module = :module: and choice = :choice:',
			'bind' => [
				'user_id' => $user_id,
				'module' => $module,
				'choice' => $choice
			]
		]);
		if ($userChoice) {
			if ($userChoice->state == CsmsConstant::STATE_NORMAL) {
			    // 编辑权限，已有直接true ，不报错
			    return true;
			}
			$userChoice->state = CsmsConstant::STATE_NORMAL;
			$userChoice->update_time = time();
			$userChoice->save();
		} else {
			$model = new CsmsUserChoice();
			// 将module_id 和 choice_id补上
			$module_info = CsmsModules::findFirst(["module = :module:", 'bind' => ['module' => $module]]);
			$module_id = $module_info ? $module_info->mid : 0;

			$choice_info = CsmsModulesChoice::findFirst([
				"mid = :module_id: and choice = :choice:",
				'bind'=>['module_id' => $module_id, 'choice' => $choice]
			]);

			$choice_id = $choice_info ? $choice_info->cid : 0;
			$model->save([
				'user_id' => $user_id,
				'module_id' => $module_id,
				'module' => $module,
				'choice_id' => $choice_id,
				'choice' => $choice,
				'state' => CsmsUserChoice::STATUS_NORMAL,
				'create_time' => time(),
				'update_time' => time()
			]);
		}
		return true;
	}

}