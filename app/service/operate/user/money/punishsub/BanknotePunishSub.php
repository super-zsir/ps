<?php


namespace Imee\Service\Operate\User\Money\Punishsub;

use Imee\Models\Xs\XsBanknoteLog;
use Imee\Models\Xs\XsUserMoney;
use Imee\Service\Helper;
use Imee\Models\Xs\BaseModel as XsBaseModel;

// 现金加钱
class BanknotePunishSub extends PunishSubAbstract
{
    public function subMoney($uid, $money)
    {
        // 美元转为币分
        $money = intval($money * 1000);
        $dealMoney = $money * 10;
        $userMoneyBN = XsUserMoney::findFirstValue($uid, true)->money_banknote ?? 0;
        if ($userMoneyBN < $money * 10) {
            $debts = $money - floor($userMoneyBN / 10);
            Helper::exec("update xs_user_money set money_debts = money_debts + {$debts} where uid = {$uid}", XsBaseModel::SCHEMA);
            $dealMoney = floor($userMoneyBN / 10) * 10;
        }

        if ($dealMoney > 0) {
            Helper::exec("update xs_user_money set money_banknote = money_banknote - {$dealMoney} where uid = {$uid}", XsBaseModel::SCHEMA);

            XsBanknoteLog::useMaster()->save([
                'uid'      => $uid,
                'money'    => $dealMoney,
                'dateline' => time(),
                'reason'   => json_encode([
                    'type'   => 'BILL_TYPE_OP_REASON_OFFICIAL_DEDUCTION_BANKNOTE',
                    'before' => ['money_banknote' => $userMoneyBN],
                    'after'  => ['money_banknote' => $userMoneyBN - $dealMoney],
                ]),
                'op'       => 22,
                'desc'     => '官方扣除',
            ]);
        }

        return ['unit' => '美金', 'vtype' => 7];
    }
}