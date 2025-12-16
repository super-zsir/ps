<?php


namespace Imee\Service\Operate\User\Pay;

abstract class PayHistoryAbstractService
{
	public $conditions;
	public $query;

	abstract public function getPayHistoryList();

	public static function getHistoryType($type): self
	{
		switch ($type) {
			case 'agent':
				return new AgentMoneyService(); // 转账钻石类型
			case 'banknote':
				return new BankNoteService(); // 现金类型
			case 'gamecoin':
				return new GameCoinService(); // 金币类型
			default:
				return new PayCharmService(); // 钻石魅力值类型
		}
	}
}