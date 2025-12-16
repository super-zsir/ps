<?php


namespace Imee\Service\Domain\Service\Csms\Process\Staff;


use Imee\Models\Xss\CsmsStaff;
use Imee\Models\Xss\CsmsUserChoice;
use Imee\Service\Domain\Service\Csms\Context\CsmsBaseContext;

class StaffDelProcess extends CsmsBaseContext
{

	public function handle()
	{
		$user_id = $this->context->userId;
		// csms_staff
		$csmsStaff = CsmsStaff::findFirst([
			'conditions' => "user_id = :user_id:",
			'bind' => [
				'user_id' => $user_id
			]
		]);
		if($csmsStaff){
			$csmsStaff->delete();
		}
		// csms_user_choice
		$userChoices = CsmsUserChoice::find([
			'conditions' => 'user_id = :user_id:',
			'bind' => [
				'user_id' => $user_id
			]
		]);
		if($userChoices){
			foreach ($userChoices as $userChoice){
				$userChoice->delete();
			}
		}
	}

}