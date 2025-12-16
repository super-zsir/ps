<?php


namespace Imee\Service\Domain\Service\Csms\Process\Csmstask;


use Imee\Models\Xss\CsmsUserChoice;
use Imee\Service\Domain\Service\Csms\Exception\CsmsStaffException;

class StaffDelProcess
{


	public $context;

	public function __construct($context)
	{
		$this->context = $context;
	}


	public function handle()
	{
		$userId = $this->context->userId;
		$module = $this->context->module;
		$choice = $this->context->choice;

		$userChoice = CsmsUserChoice::findFirst([
			'conditions' => 'user_id = :user_id: and module = :module: and choice = :choice: and group_id = 0',
			'bind' => [
				'user_id' => $userId,
				'module' => $module,
				'choice' => $choice
			]
		]);
		if (!$userChoice) {
			CsmsStaffException::throwException(CsmsStaffException::USER_CHOICE_NOT_EXIST);
		}
		$userChoice->delete();
		return true;
	}

}