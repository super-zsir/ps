<?php


namespace Imee\Service\Operate\User\Money\Punishsub;

use Imee\Models\Xs\XsPayChangeNew;
use Imee\Models\Xs\XsUserMoney;
use Imee\Service\Helper;
use Imee\Models\Xs\BaseModel as XsBaseModel;

// 虚拟币
class MoneyBPunishSub extends PunishSubAbstract
{
    public function subMoney($uid, $money)
    {
        $dealMoney = $money;
        $userMoneyB = XsUserMoney::findFirstValue($uid, true)->money_b ?? 0;
        if ($userMoneyB < $money) {
            $debts = $money - $userMoneyB;
            Helper::exec("update xs_user_money set money_debts = money_debts + {$debts} where uid = {$uid}", XsBaseModel::SCHEMA);
            $dealMoney = $userMoneyB;
        }
        if ($dealMoney > 0) {
            Helper::exec("update xs_user_money set money_b = money_b - {$dealMoney} where uid = {$uid}", XsBaseModel::SCHEMA);
            XsPayChangeNew::logNew($uid, $money, 'extend', Helper::translate($uid, '官方扣除'), [
                'type'   => 'BILL_TYPE_OP_REASON_OFFICIAL_DEDUCTION_DIAMOND',
                'before' => ['money_b' => $userMoneyB],
                'after'  => ['money_b' => $userMoneyB - $dealMoney],
            ]);
        }
        return ['unit' => '钻石', 'vtype' => 9];
    }
}