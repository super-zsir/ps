<?php

namespace Imee\Service\Operate\User\Money\Punishsub;

use Imee\Models\Rpc\PsRpc;
use Imee\Models\Xs\XsUserMoney;
use Imee\Service\Helper;
use Imee\Models\Xs\BaseModel as XsBaseModel;

class GameCoinPunishSub extends PunishSubAbstract
{
    public function subMoney($uid, $money)
    {
        $coin = XsUserMoney::getGameAccount([$uid]);
        $userCoin = $coin[$uid]['Char.Coin'] ?? 0;

        $dealMoney = $money;
        if ($userCoin < $money) {
            $debts = $money - $userCoin;
            Helper::exec("update xs_user_money set money_debts = money_debts + {$debts} where uid = {$uid}", XsBaseModel::SCHEMA);
            $dealMoney = $userCoin;
        }

        if ($dealMoney > 0) {
            $this->modifyGameCoin($uid, $dealMoney);
        }
        return ['unit' => '金币', 'vtype' => 8];
    }

    // 调用rpc接口，扣除金币
    public function modifyGameCoin($uid, $money)
    {
        if ($money <= 0) return 0;
        $now = time();
        $data = [
            'order_id'   => "admin_{$uid}_0_{$now}",
            'user_id'    => $uid,
            'money_type' => 0,
            'op'         => 1, // 操作类型，0 增加，1 减少
            'op_detail'  => 4, // 0 充值, 1 消费, 2 收入, 3 提现, 4 官方没收, 5 原路退款, 6 补贴, 7 赠送, 8 代冲, 9 游戏下注, 10 游戏结算, 11 任务奖励
            'amount'     => $money,
            'ts'         => $now,
        ];

        list($res, $_) = (new PsRpc())->call(PsRpc::API_GAME_COIN_MODIFY, ['json' => $data]);
        // Helper::debugger()->error('游戏币添加结果：' . json_encode($res, 256));
        if (empty($res['success'])) {
            throw new \Exception('金币加钱失败：' . json_encode($res, 256));
        }
        return $res['data']['balance'][0]['amount'] ?? 0;
    }
}