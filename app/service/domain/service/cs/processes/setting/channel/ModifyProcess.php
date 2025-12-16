<?php

namespace Imee\Service\Domain\Service\Cs\Processes\Setting\Channel;

use Imee\Exception\Cs\CommonException;
use Imee\Models\Cms\CmsChatService;
use Imee\Service\Domain\Context\Cs\Setting\Channel\ModifyContext;

class ModifyProcess
{
    private $context;
    public function __construct(ModifyContext $context)
    {
        $this->context = $context;
    }

    public function handle()
    {
		$rec = CmsChatService::findFirst([
			'conditions' => 'id = :id:',
			'bind' => [
				'id' => $this->context->id,
			],
		]);

		if(empty($rec)){
			CommonException::throwException(CommonException::RECORD_NOT_FOUND);
		}

		$data = [];
		if (!empty($this->context->language)) {
			foreach ($this->context->language as $lang) {
				$data[APP_ID][] = $lang;
			}
		}

		$rec->language = json_encode($data);
		if (!$rec->save()) {
			CommonException::throwException(CommonException::MODIFY_FAILED);
		}
		return true;
    }
}
