<?php


namespace Imee\Service\Operate\User\Money\Punishsub;

use Imee\Models\Xs\XsPayChangeNew;
use Imee\Models\Xs\XsUserMoney;
use Imee\Service\Helper;
use Imee\Models\Xs\BaseModel as XsBaseModel;

// 金豆
class GoldPunishSub extends PunishSubAbstract
{
    public function subMoney($uid, $money)
    {
        $dealMoney = $money;
        $goldCoin = XsUserMoney::findFirstValue($uid, true)->gold_coin ?? 0;
        if ($goldCoin < $money) {
            $debts = $money - $goldCoin;
            Helper::exec("update xs_user_money set money_debts = money_debts + {$debts} where uid = {$uid}", XsBaseModel::SCHEMA);
            $dealMoney = $goldCoin;
        }

        if ($dealMoney > 0) {
            Helper::exec("update xs_user_money set gold_coin = gold_coin - {$dealMoney} where uid = {$uid}", XsBaseModel::SCHEMA);
            XsPayChangeNew::logNew($uid, $money, 'extend', Helper::translate($uid, '官方扣除'), [
                'type'   => 'BILL_TYPE_OP_REASON_OFFICIAL_DEDUCTION',
                'before' => ['gold_coin' => $goldCoin],
                'after'  => ['gold_coin' => $goldCoin - $dealMoney],
            ]);
        }
        return ['unit' => '金豆', 'vtype' => 6];
    }
}