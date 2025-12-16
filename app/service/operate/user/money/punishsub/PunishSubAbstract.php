<?php


namespace Imee\Service\Operate\User\Money\Punishsub;

abstract class PunishSubAbstract
{
	abstract public function subMoney($uid, $money);

    //  0充值余额、4代充转账钻石、5魅力值、6金豆、7现金、8金币 10直播魅力值
	public static function getPunishType($type) :self
	{
		switch ($type) {
			case 'money':
				return new MoneyPunishSub();
			case 'money_b':
				return new MoneyBPunishSub();
			case 'agent_user_money':
				return new AgentMoneyPunishSub();
			case 'money_cash':
				return new CashPunishSub();
			case 'gold_coin':
				return new GoldPunishSub();
			case 'money_banknote':
				return new BanknotePunishSub();
			case 'game_coin':
				return new GameCoinPunishSub();
            case 'money_cash_live':
                return new CashLivePunishSub();
			default:
				throw new \Exception('error account type');
		}
	}
}