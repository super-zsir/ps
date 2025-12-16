<?php

namespace Imee\Service\Operate\User\Pay;

use Imee\Models\Xs\XsAgentPayChangeFlow;

/**
 * 用户账户变化历史【转账钻石】类型
 */
class AgentMoneyService extends PayHistoryAbstractService
{
    public function getPayHistoryList(): array
    {
        $columns = ['columns' => 'id, uid, dateline, agent_money as money, op, reason, trade_detail'];
        $total = intval(XsAgentPayChangeFlow::count($this->conditions));
        if ($total === 0) {
            return array('data' => [], 'total' => 0);
        }
        $data = XsAgentPayChangeFlow::find(array_merge([$this->conditions], $this->query, $columns))->toArray();
        foreach ($data as &$val) {
            $val['dateline'] = date('Y-m-d H:i:s', $val['dateline']);
            $val['reason_display'] = $val['reason'];
            $val['reason'] = !empty($val['trade_detail']) ? @json_decode($val['trade_detail'], true) : '';
            $val['reason_person'] = highlight_string(var_export($val['reason'], true), true);
            $val['extra'] = '';
            $val['to'] = $val['reason']['receiver_id'] ?? ($val['reason']['id'] ?? 0);
            $val['op'] = XsAgentPayChangeFlow::OP_REASON[$val['op']] ?? $val['op'];
        }
        return ['data' => $data, 'total' => $total];
    }
}