<?php

namespace Imee\Service\Operate\User\Pay;

use Imee\Models\Xs\XsBanknoteLog;

/**
 * 用户账户变化历史【现金】类型
 */
class BankNoteService extends PayHistoryAbstractService
{
    public function getPayHistoryList(): array
    {
        $total = intval(XsBanknoteLog::count($this->conditions));
        if ($total === 0) {
            return array('data' => [], 'total' => 0);
        }
        $data = XsBanknoteLog::find(array_merge([$this->conditions], $this->query))->toArray();
        foreach ($data as &$val) {
            $val['dateline'] = date('Y-m-d H:i:s', $val['dateline']);
            $val['reason'] = !empty($val['reason']) ? @json_decode($val['reason'], true) : '';
            $val['reason_person'] = highlight_string(var_export($val['reason'], true), true);
            $val['reason_display'] = $val['desc'];
            $val['extra'] = '';
            $val['to'] = $val['reason']['to'] ?? 0;
            $val['op'] = XsBanknoteLog::OP_TRANSLATE[$val['op']] ?? $val['op'];
        }
        return ['data' => $data, 'total' => $total];
    }
}