<?php


namespace Imee\Service\Domain\Service\Audit\Processes\Csmsaudit;


use Imee\Models\Xss\CsmsAudit;
use Imee\Service\Domain\Service\Csms\Context\Staff\OldTaskInfoContext;

class OldTaskInfoProcess
{

	protected $context;

	public function __construct(OldTaskInfoContext $context)
	{
		$this->context = $context;
	}

	public function handle()
	{
		$data = CsmsAudit::find([
			'conditions' => 'id in ({ids:array})',
			'bind' => [
				'ids' => $this->context->ids
			]
		])->toArray();
		return array_column($data, null, 'id');
	}



}