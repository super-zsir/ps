<?php

namespace Imee\Models\Xs;

use Imee\Models\Xsst\XsstUserAgentMoney;
use Imee\Models\Xsst\XsstUserAgentSendPackageLog;
use Imee\Service\Helper;

class XsPay extends BaseModel
{
	const MONEY_TYPE_MAP = [
		'USD' => '美元',
		'CNY' => '人民币',
		'TWD' => '台币'
	];

	const STATE_MAP = [
		'sent' => '未支付',
		'success' => '已支付',
	];

	public static function getUserTotalRechargeMoney($uid): int
	{
		$pays = self::findFirst(
			array(
				'conditions' => 'uid = :uid: and state = :state:',
				'columns' => 'sum(money) as money',
				'bind' => array('uid' => $uid, 'state' => 'success')
			)
		);
		return (int)$pays->money;
	}

    /**
     * 获取首次充值的时间
     * 包含直充、代充红包、GS魅力值换钻石、大额自充充值、自充工资兑换钻石
     * @param $uid
     * @return int|mixed
     */
    public static function getFirstPayTime($uid)
    {
        $time = 0;
        if ($uid) {
            //直充
            $pay = XsPay::findFirst([
                'conditions' => 'uid = :uid: and state = :state:',
                'bind'       => array(
                    'uid'   => $uid,
                    'state' => 'success'
                ),
                'order'      => 'id asc'
            ]);
            if ($pay) {
                $time = $pay->end_time;
            }

            //大额自充
            $payAgent = XsPayAgentmoney::findFirst([
                'conditions' => 'uid = :uid: and state = :state:',
                'bind'       => array(
                    'uid'   => $uid,
                    'state' => 1
                ),
                'order'      => 'id asc'
            ]);
            if ($payAgent) {
                $time = min($payAgent->dateline, $time);
            }

            //魅力值换钻石 自充工资兑换钻石
            $agentMoney = XsstUserAgentMoney::findFirst([
                'conditions' => 'uid = :uid: and state = :state:',
                'bind'       => array(
                    'uid'   => $uid,
                    'state' => 1
                ),
                'order'      => 'id asc'
            ]);
            if ($agentMoney) {
                $time = min($agentMoney->dateline, $time);
            }

            //代充发红包
            $package = XsstUserAgentSendPackageLog::findFirst([
                'conditions' => 'touid = :touid:',
                'bind'       => array(
                    'touid' => $uid
                ),
                'order'      => 'id asc'
            ]);
            if ($package) {
                $time = min($package->dateline, $time);
            }
        }
        return $time;
    }

    public static function getFirstPayTimeByUids(array $uids): array
    {
        $uids = array_filter($uids);
        $uids = array_map('intval', $uids);
        $uids = array_values(array_unique($uids));
        if (!$uids) {
            return [];
        }

        $uidStr = implode(',', $uids);

        $sql = "SELECT t.id, t.uid, t.end_time
                FROM xs_pay t
                INNER JOIN (
                    SELECT uid, MIN(id) AS min_id
                    FROM xs_pay
                    WHERE uid IN ({$uidStr}) AND state = 'success'
                    GROUP BY uid
                ) AS sub ON t.id = sub.min_id AND t.uid = sub.uid";

        $pays = Helper::fetch($sql, null, XsPay::SCHEMA_READ);
        $pays = array_column($pays, 'end_time', 'uid');

        $sql = "SELECT t.id, t.uid, t.dateline
                FROM xs_pay_agentmoney t
                INNER JOIN (
                    SELECT uid, MIN(id) AS min_id
                    FROM xs_pay_agentmoney
                    WHERE uid IN ({$uidStr}) AND state = 1
                    GROUP BY uid
                ) AS sub ON t.id = sub.min_id AND t.uid = sub.uid";

        $agentMoneys = Helper::fetch($sql, null, XsPayAgentmoney::SCHEMA_READ);
        $agentMoneys = array_column($agentMoneys, 'dateline', 'uid');

        $sql = "SELECT t.id, t.uid, t.dateline
                FROM xsst_user_agent_money t
                INNER JOIN (
                    SELECT uid, MIN(id) AS min_id
                    FROM xsst_user_agent_money
                    WHERE uid IN ({$uidStr}) AND state = 1
                    GROUP BY uid
                ) AS sub ON t.id = sub.min_id AND t.uid = sub.uid";

        $xsstAgentMoneys = Helper::fetch($sql, null, XsstUserAgentMoney::SCHEMA_READ);
        $xsstAgentMoneys = array_column($xsstAgentMoneys, 'dateline', 'uid');

        $sql = "SELECT t.id, t.touid, t.dateline
                FROM xsst_user_agent_send_package_log t
                INNER JOIN (
                    SELECT touid, MIN(id) AS min_id
                    FROM xsst_user_agent_send_package_log
                    WHERE touid IN ({$uidStr})
                    GROUP BY touid
                ) AS sub ON t.id = sub.min_id AND t.touid = sub.touid";

        $packageLogs = Helper::fetch($sql, null, XsstUserAgentSendPackageLog::SCHEMA_READ);
        $packageLogs = array_column($packageLogs, 'dateline', 'touid');

        $data = [];
        foreach ($uids as $uid) {
            $data[$uid] = 0;

            $times = [];
            if (!empty($pays[$uid])) {
                $times[] = $pays[$uid];
            }
            if (!empty($agentMoneys[$uid])) {
                $times[] = $agentMoneys[$uid];
            }
            if (!empty($xsstAgentMoneys[$uid])) {
                $times[] = $xsstAgentMoneys[$uid];
            }
            if (!empty($packageLogs[$uid])) {
                $times[] = $packageLogs[$uid];
            }

            if ($times) {
                $data[$uid] = min($times);
            }
        }

        return $data;
    }
}
