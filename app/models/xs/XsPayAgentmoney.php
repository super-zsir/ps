<?php

namespace Imee\Models\Xs;

class XsPayAgentmoney extends BaseModel
{
    protected $allowEmptyStringArr = [
        'original_money_type', 'original_money'
    ];

    const STATE_PENDING_REVIEW = 0; // 待审核
    const STATE_PASSED = 1; // 已通过
    const STATE_REFUSE = 2; // 已拒绝
    const STATE_DEPOSIT = 3; // 押金
    const STATE_DEPOSIT_REFUND = 4; // 押金已退
    const STATE_PASSED_WITHOUT_TRIAL = 5; // 免审通过
    const STATE_SYSTEM_PASS = 6; // 系统自动通过

    const STATE_MAP = [
        self::STATE_PENDING_REVIEW       => '待审核',
        self::STATE_PASSED               => '已通过',
        self::STATE_REFUSE               => '已拒绝',
        self::STATE_DEPOSIT              => '押金',
        self::STATE_DEPOSIT_REFUND       => '押金已退',
        self::STATE_PASSED_WITHOUT_TRIAL => '免审通过',
        self::STATE_SYSTEM_PASS          => '系统自动通过',
    ];

    const FINANCE_STATE_MAP = [
        1 => '待确认',
        2 => '确认收到',
        3 => '确认未收到',
    ];

    const RECHARGE_MAP = [
        0 => '银行转账',
        1 => 'payoneer转账',
        2 => '支付宝转账',
        3 => '沙特银行转账自动到账',
        4 => '菲律宾银行转账自动到账',
        5 => 'epay转账',
        6 => '埃及本币转账',
    ];

    const ACCOUNT_MAP = [
        '0' => [ // 银行转账
            '0002,OP-HSBC,SGD,041845744001',
            '0004,OP-HSBC,USD,260529524178',
            '0056,OP-Wise,USD,8311103359',
            '0024,OP-Pyvio,VND,000918764784015',
            '0024,OP-Pyvio,THB,4013442848',
            '0024,OP-Pyvio,IDR,1168803537316210',
            '0057,OT-CIMB,MYR,8011082441',
            '0035,OP-Airwallex,USD,8452404164',
        ],
        '1' => [ // payoneer
            '0054,OP-Payoneer,USD,Paris@olaparty.sg',
            '0058,OT-Payoneer,USD,Huiyuan.pt@olaparty.sg'
        ],
        '3' => [ // 沙特银行转账
        ],
        '4' => [ // 菲律宾银行转账
            '0037,OP-Payermax,PHP,900180001020'
        ],
        '5' => [ // epay
            '0050,OP-EPAY,USD,Payment@olaparty.sg'
        ],
        '6' => [ // 埃及本币转账
            '0060,Khaled,EGP,xxx'
        ],
    ];

    /**
     * 获取用户押金，计算公式=状态为“押金”的“需加币数”-“押金已退”的“需加币数”
     * @param $uid
     * @return int
     */
    public static function getUserDeposit($uid): int
    {
        $result = 0;
        if (empty($uid)) {
            return $result;
        }
        $agentMoney = self::find([
            'columns' => 'sum(moneyadd) as money, state',
            'uid = :uid: and state IN ({states:array})',
            'bind'    => ['uid' => $uid, 'states' => [self::STATE_DEPOSIT, self::STATE_DEPOSIT_REFUND]],
            'group'   => 'state'
        ])->toArray();
        if (empty($agentMoney)) {
            return $result;
        }
        $stateMoney = array_column($agentMoney, 'money', 'state');
        $depositMoney = $stateMoney[self::STATE_DEPOSIT] ?? 0;
        $refundMoney = $stateMoney[self::STATE_DEPOSIT_REFUND] ?? 0;
        return $depositMoney >= $refundMoney ? $depositMoney - $refundMoney : 0;
    }


    /**
     * 获取用户已免审额数据，免审额计算公式=押金额-当日免审额，押金额和免审额均取“需加币数”
     * @param $uid
     * @return int
     */
    public static function getUserExemptionMoney($uid): int
    {
        $todayStart = strtotime(date("Y-m-d"));
        $todayEnd = $todayStart + 86400;
        $agentMoney = self::findFirst([
            'columns' => 'sum(moneyadd) as money',
            'uid = :uid: and state = :state: and updatetime>:st: and updatetime <:et: and finance_state != 2',
            'bind'    => ['uid' => $uid, 'state' => self::STATE_PASSED_WITHOUT_TRIAL, 'st' => $todayStart, 'et' => $todayEnd],
        ])->toArray();
        return (int)$agentMoney['money'];
    }


    /**
     * 获取用户当月转账金额
     * @param $uid
     * @param $id
     * @return int
     */
    public static function getUserCurrentMonthMoney($uid, $id): int
    {
        if ($id > 0) { // 编辑时，查询当月转账金额
            $agent = self::findFirst(['columns' => 'dateline', 'id = :id:', 'bind' => ['id' => $id]])->toArray();
            if (empty($agent['dateline'])) {
                return 0;
            }
            $month = \Imee\Service\Helper::getDateMonthDur($agent['dateline']);
            $agentMoney = self::findFirst([
                'columns'    => 'sum(money) as amount',
                'conditions' => 'state IN ({state:array}) and uid = :uid: and updatetime>=:st: and updatetime <:et: and id < :id:',
                'bind'       => ['uid' => $uid, 'state' => [self::STATE_PASSED, self::STATE_PASSED_WITHOUT_TRIAL, self::STATE_SYSTEM_PASS], 'st' => $month['start'], 'et' => $month['end'] + 86400, 'id' => $id],
            ])->toArray();
        } else {
            $time = time();
            $month = \Imee\Service\Helper::getDateMonthDur($time);
            $agentMoney = self::findFirst([
                'columns' => 'sum(money) as amount',
                'uid = :uid: and state IN ({state:array}) and updatetime>=:st: and updatetime <:et:',
                'bind'    => ['uid' => $uid, 'state' => [self::STATE_PASSED, self::STATE_PASSED_WITHOUT_TRIAL, self::STATE_SYSTEM_PASS], 'st' => $month['start'], 'et' => $month['end'] + 86400],
            ])->toArray();
        }
        return bcdiv($agentMoney['amount'], 100, 2); // 美元
    }
}
