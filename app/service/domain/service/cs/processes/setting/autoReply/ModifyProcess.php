<?php

namespace Imee\Service\Domain\Service\Cs\Processes\Setting\AutoReply;

use Imee\Exception\Cs\CommonException;
use Imee\Models\Xss\XssAutoQuestion;
use Imee\Service\Domain\Context\Cs\Setting\AutoReply\ModifyContext;

class ModifyProcess
{
    private $context;
    public function __construct(ModifyContext $context)
    {
        $this->context = $context;
    }

    public function handle()
    {
        $rec = XssAutoQuestion::findFirst($this->context->id);
        if (!$rec) {
        	CommonException::throwException(CommonException::RECORD_NOT_FOUND);
        }
        $data = [
            'hot' => $this->context->hot,
            'tag' => $this->context->tag,
            'subject' => $this->context->subject,
            'answer' => $this->context->answer,
            'guide_to_service' => $this->context->guideToService,
            'type' => $this->context->type,
            'language' => $this->context->language,
        ];
        if (!$rec->save($data)) {
			CommonException::throwException(CommonException::MODIFY_FAILED);
		}

        return true;
    }
}
