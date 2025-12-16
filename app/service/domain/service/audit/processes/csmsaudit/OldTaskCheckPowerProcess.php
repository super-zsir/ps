<?php


namespace Imee\Service\Domain\Service\Audit\Processes\Csmsaudit;


use Imee\Helper\Constant\AuditConstant;
use Imee\Service\Domain\Service\Csms\Context\Staff\OldTaskCheckPowerContext;

class OldTaskCheckPowerProcess
{

	protected $context;

	public function __construct(OldTaskCheckPowerContext $context)
	{
		$this->context = $context;
	}


	public function handle()
	{
		if (!$this->context->power || !$this->context->info) {
			return false;
		}
		$type = $this->context->info[AuditConstant::NEW_TASK_FIELD];
		if (in_array($type, $this->context->power)) {
			return true;
		}
		return false;
	}


}