<?php
namespace Imee\Service\Domain\Service\Cs\Processes\Setting\AutoReply;

use Imee\Models\Xss\XssAutoQuestion;
use Imee\Service\Domain\Context\Cs\Setting\AutoReply\CreateContext;
use Imee\Service\Helper;

class CreateProcess
{
    private $context;
    public function __construct(CreateContext $context)
    {
        $this->context = $context;
    }

    public function handle()
    {
		$rec = new XssAutoQuestion();
		$rec->uid = Helper::getSystemUid();
		$rec->dateline = time();
		$rec->hot = $this->context->hot;
		$rec->tag = $this->context->tag;
		$rec->subject = $this->context->subject;
		$rec->answer = $this->context->answer;
		$rec->app_id = APP_ID;
		$rec->guide_to_service = $this->context->guideToService;
		$rec->type = $this->context->type;
		$rec->language = $this->context->language;

		return $rec->save();
    }
}
