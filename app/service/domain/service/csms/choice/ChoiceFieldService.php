<?php


namespace Imee\Service\Domain\Service\Csms\Choice;


use Imee\Models\Xss\CsmsChoiceField;
use Imee\Service\Domain\Service\Csms\Choice\Context\Choicefield\ChoiceFieldInfoContext;

class ChoiceFieldService
{



	public function info($params = [])
	{
		$context = new ChoiceFieldInfoContext($params);
		$choice = $context->choice ?? '';
		if($choice){
			$choiceField = CsmsChoiceField::find([
				'conditions' => "choice = :choice:",
				'bind' => [
					'choice' => $choice
				]
			])->toArray();
			return $choiceField;
		}
		return [];
	}


}