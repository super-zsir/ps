<?php


namespace Imee\Service\Operate\User\Money\Punishsub;

use Imee\Models\Xs\XsBrokerUser;
use Imee\Models\Xs\XsPayChangeNew;
use Imee\Models\Xs\XsUserMoney;
use Imee\Service\Helper;
use Imee\Models\Xs\BaseModel as XsBaseModel;

// 魅力值罚款
class CashPunishSub extends PunishSubAbstract
{
    public function subMoney($uid, $money)
    {
        $cashes = XsUserMoney::findFirstValue($uid, true);

        $userCash = $cashes->money_cash ?? 0;
        $userCashB = $cashes->money_cash_b ?? 0;
        if ($userCash + $userCashB < $money) {
            $debts = $money - $userCash - $userCashB;
            Helper::exec("update xs_user_money set money_debts = money_debts + {$debts} where uid = {$uid}", XsBaseModel::SCHEMA);
            $dealCash = $userCash;
            $dealCashB = $userCashB;
        } elseif ($userCash < $money) {
            $dealCash = $userCash;
            $dealCashB = $money - $userCash;
        } else {
            $dealCash = $money;
            $dealCashB = 0;
        }

        if ($dealCash + $dealCashB > 0) {
            Helper::exec("update xs_user_money set money_cash = money_cash - {$dealCash},money_cash_b = money_cash_b - {$dealCashB} where uid = {$uid}", XsBaseModel::SCHEMA);

            XsPayChangeNew::logNew($uid, $money, 'extend', Helper::translate($uid, '官方扣除'), [
                'type'   => 'BILL_TYPE_OP_REASON_OFFICIAL_DEDUCTION_CHARM',
                'before' => ['money_cash' => $userCash, 'money_cash_b' => $userCashB],
                'after'  => ['money_cash' => $userCash - $dealCash, 'money_cash_b' => $userCashB - $dealCashB],
            ]);
        }
        $unit = XsBrokerUser::isGs($uid) ? '魅力值' : '金豆';
        return ['unit' => $unit, 'vtype' => 5];
    }
}