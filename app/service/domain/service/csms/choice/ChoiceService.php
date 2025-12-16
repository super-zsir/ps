<?php


namespace Imee\Service\Domain\Service\Csms\Choice;



use Imee\Models\Xss\CsmsChoice;
use Imee\Service\Domain\Service\Csms\Choice\Context\Choice\InfoContext;

class ChoiceService
{


	/**
	 * 获取审核项信息
	 * @param array $params
	 * @return array
	 */
	public function info($params = [])
	{
		$context = new InfoContext($params);
		$choice = $context->choice ?? '';
		if($choice){
			$choiceInfo = CsmsChoice::findFirst([
				'conditions' => 'choice = :choice:',
				'bind' => [
					'choice' => $choice
				]
			]);
			return $choiceInfo ? $choiceInfo->toArray() : [];
		}
		return [];
	}



}