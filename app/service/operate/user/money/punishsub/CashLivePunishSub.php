<?php

namespace Imee\Service\Operate\User\Money\Punishsub;

use Imee\Exception\ApiException;
use Imee\Models\Xs\XsPayChangeNew;
use Imee\Models\Xs\XsUserMoney;
use Imee\Service\Helper;
use Imee\Models\Xs\BaseModel as XsBaseModel;

class CashLivePunishSub extends PunishSubAbstract
{
    public function subMoney($uid, $money)
    {
        $cashLive = XsUserMoney::findFirstValue($uid, true)->money_cash_live ?? 0;

        if ($cashLive < $money) {
            throw new ApiException(ApiException::MSG_ERROR, sprintf('当前该用户的账户余额为%s，请调整要扣除的金额后再试', $cashLive));
        }

        Helper::exec("update xs_user_money set money_cash_live = money_cash_live - {$money},money_cash_live_consume = money_cash_live_consume + {$money} where uid = {$uid}", XsBaseModel::SCHEMA);

        XsPayChangeNew::logNew($uid, $money, 'extend', '官方扣除', [
            'type'   => 'BILL_TYPE_OP_REASON_OFFICIAL_DEDUCTION_CHARM_MONEY_LIVE',
            'before' => ['money_cash_live' => $cashLive],
            'after'  => ['money_cash_live' => $cashLive - $money],
        ]);

        return ['unit' => '魅力值', 'vtype' => 10];
    }
}