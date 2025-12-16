<?php


namespace Imee\Service\Operate\User\Money\Punishsub;

use Imee\Models\Xs\XsPayChangeNew;
use Imee\Models\Xs\XsUserMoney;
use Imee\Service\Helper;
use Imee\Models\Xs\BaseModel as XsBaseModel;

// 余额
class MoneyPunishSub extends PunishSubAbstract
{
    public function subMoney($uid, $money)
    {
        $dealMoney = $money;
        $userMoney = XsUserMoney::findFirstValue($uid, true)->money ?? 0;
        if ($userMoney < $money) {
            $debts = $money - $userMoney;
            Helper::exec("update xs_user_money set money_debts = money_debts + {$debts} where uid = {$uid}", XsBaseModel::SCHEMA);
            $dealMoney = $userMoney; //如果罚款不够 ，就把用户剩余的钱 全部扣除，用户money就变为0
        }
        if ($dealMoney > 0) {
            Helper::exec("update xs_user_money set money = money - {$dealMoney} where uid = {$uid}", XsBaseModel::SCHEMA);

            XsPayChangeNew::logNew($uid, $money, 'extend', Helper::translate($uid, '官方扣除'), [
                'type'   => 'BILL_TYPE_OP_REASON_OFFICIAL_DEDUCTION_DIAMOND',
                'before' => ['money' => $userMoney],
                'after'  => ['money' => $userMoney - $dealMoney],
            ]);
        }
        return ['unit' => '钻石', 'vtype' => 0];
    }
}