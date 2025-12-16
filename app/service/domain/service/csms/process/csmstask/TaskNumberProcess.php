<?php


namespace Imee\Service\Domain\Service\Csms\Process\Csmstask;


use Imee\Models\Xss\CsmsTasklimit;
use Imee\Service\Domain\Service\Csms\Context\Csmstask\TaskNumberContext;

class TaskNumberProcess
{


	public function __construct(TaskNumberContext $context)
	{
		$this->context = $context;
	}


	public function handle()
	{
		$module = $this->context->module;
		$choice = $this->context->choice;
		$number = $this->context->number;


		$taskLimit = CsmsTasklimit::findFirst([
			'conditions' => "module = :module: and choice = :choice:",
			'bind' => [
				'module' => $module,
				'choice' => $choice
			]
		]);
		if ($taskLimit) {
			$taskLimit->number = $number;
			$taskLimit->dateline = time();
			$taskLimit->save();
		} else {
			$model = new CsmsTasklimit();
			$model->save([
				'module' => $module,
				'choice' => $choice,
				'number' => $number,
				'dateline' => time()
			]);
		}
		return true;
	}

}