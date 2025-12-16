<?php


namespace Imee\Service\Operate\User\Money\Punishsub;

use Imee\Models\Xs\XsAgentPayChangeFlow;
use Imee\Models\Xs\XsUserMoney;
use Imee\Service\Helper;
use Imee\Models\Xs\BaseModel as XsBaseModel;

// 代充钻石
class AgentMoneyPunishSub extends PunishSubAbstract
{
    public function subMoney($uid, $money)
    {
        $dealMoney = $money;
        $agentMoney = XsUserMoney::findFirstValue($uid, true)->agent_user_money ?? 0;
        if ($agentMoney < $money) {
            $debts = $money - $agentMoney;
            Helper::exec("update xs_user_money set money_debts = money_debts + {$debts} where uid = {$uid}", XsBaseModel::SCHEMA);
            $dealMoney = $agentMoney;
        }
        if ($dealMoney > 0) {
            Helper::exec("update xs_user_money set agent_user_money = agent_user_money - {$dealMoney} where uid = {$uid}", XsBaseModel::SCHEMA);

            XsAgentPayChangeFlow::useMaster()->save([
                'uid'          => $uid,
                'dateline'     => time(),
                'agent_money'  => $dealMoney,
                'op'           => 'extend',
                'reason'       => '',
                'trade_detail' => json_encode([
                    'type'          => 'BILL_TYPE_OP_REASON_OFFICIAL_DEDUCTION_AGENT',
                    'sender_id'     => 0,
                    'sender_name'   => '[official punish]',
                    'charge_method' => 7,
                    'charge_money'  => 0,
                ]),
            ]);
        }
        return ['unit' => '钻石', 'vtype' => 4, 'msg_unit' => '币商钻石'];
    }
}