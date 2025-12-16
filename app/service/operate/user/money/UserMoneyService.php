<?php


namespace Imee\Service\Operate\User\Money;


use Imee\Comp\Common\Phpnsq\NsqClient;
use Imee\Comp\Common\Sdk\SdkSlack;
use Imee\Exception\ApiException;
use Imee\Helper\Constant\NsqConstant;
use Imee\Helper\Traits\SingletonTrait;
use Imee\Models\Xs\BaseModel as XsBaseModel;
use Imee\Models\Xs\XsPayPunishLog;
use Imee\Models\Xs\XsUserBigarea;
use Imee\Models\Xs\XsUserMoney;
use Imee\Models\Xs\XsUserProfile;
use Imee\Service\Helper;
use Phalcon\Di;
use Imee\Service\Operate\User\Money\Punishsub\PunishSubAbstract;

class UserMoneyService
{
    use SingletonTrait;

    const OPERATE_TYPE_FIELDS = ['money' => 'money', 'money_b' => 'money_b', 'money_order' => 'money_order', 'money_order_b' => 'money_order_b', 'money_cash' => 'money_cash', 'money_cash_b' => 'money_cash_b', 'gold_coin' => 'gold_coin', 'money_lock' => 'money_lock', 'money_debts' => 'money_debts', 'agent_user_money' => 'agent_user_money', 'money_extend1' => 'money_banknote'];

    // 用户金额账户变动，信息同步到xsst_user_money_day
    public static function moneyChangeSync($records, $op, $opTime)
    {
        if (empty($records) || !in_array($op, ['write', 'update'])) return false;

        if ($op == 'update') {
            $records = array_column($records, 'after');
        }

        $date = strtotime(date('Y-m-d', $opTime));
        $insert = [];
        foreach ($records as $record) {
            $uid = $record['uid'];
            $tmpInsert = ['uid' => $uid, 'dateline' => $date, 'money_extend2' => 0];
            foreach (self::OPERATE_TYPE_FIELDS as $key => $field) {
                $tmpInsert[$key] = $record[$field] ?? 0;
            }
            $insert[] = [
                'cmd'  => 'mq.write',
                'data' => $tmpInsert
            ];
        }

        NsqClient::publishMulti(NsqConstant::TOPIC_XSST_USER_MONEY_DAY, $insert);
    }

    // 指定账户罚款
    public function accountPunish($data, $admin): array
    {
        $type = 4;
        $curtime = time();
        $msgs = [];

        // 增加对【金额异常】消息的处理
        $reason_msg = '';
        if ($data['reason'] == '金额异常') {
            if (empty($data['frozen_type']) || !in_array($data['frozen_type'], ['cancelOrder', 'chasingMoney'])) {
                return [false, '【金额异常】原因下的账户类型不能为空'];
            }
            if ($data['frozen_type'] == 'chasingMoney') { // 追款
                if (empty($data['refund_uids'])) {
                    return [false, '在原因为【金额异常】、类型为【追款】时，退款用户uid不能为空'];
                }
                $reason_msg = '指定账户罚款并且金额异常的追款类型';
            } else { // 撤单
                $reason_msg = '指定账户罚款并且金额异常的撤单类型';
            }
        }

        $conn = Di::getDefault()->getShared(XsBaseModel::SCHEMA);
        $conn->begin();
        $lockKey = 'Pay' . $data['uid'];
        try {
            $r = Helper::getLock($lockKey);
            if (!is_numeric($r) || $r != 1) {
                throw new \Exception('加锁失败，请稍候重试');
            }
            $accountUnit = PunishSubAbstract::getPunishType($data['account'])->subMoney(intval($data['uid']), floatval($data['money']));
            // 原因为金额异常的消息推送文案修改
            if (!empty($reason_msg)) {
                $translate_params = [
                    'money' => $data['money'],
                    'unit'  => Helper::translate($data['uid'], $accountUnit['msg_unit'] ?? $accountUnit['unit'])
                ];
                !empty($data['refund_uids']) && $translate_params['refundUid'] = $data['refund_uids']; // 追款用户uid
                $reason = Helper::translate($data['uid'], $reason_msg, $translate_params);
            } else {
                $reason = Helper::translate($data['uid'], '指定账户罚款', ['reason' => Helper::translate($data['uid'], $data['reason']), 'money' => $data['money'], 'unit' => Helper::translate($data['uid'], $accountUnit['unit'])]);
            }
            // 确保money是有效的数值
            if (!is_numeric($data['money'])) {
                throw new \Exception('金额必须是有效的数值');
            }
            // 确保account是有效的字符串
            if (!is_string($data['account'])) {
                throw new \Exception('账户类型必须是有效的字符串');
            }
            $multiplier = ($data['account'] === 'money_banknote') ? 1000 : 1;
            $money = intval(floatval($data['money']) * $multiplier);
            $punish = XsPayPunishLog::useMaster();
            $punish->save(['uid' => $data['uid'], 'type' => $type, 'money' => $money, 'admin' => $admin, 'reason' => $reason, 'mark' => $data['mark'] ?? '', 'dateline' => $curtime, 'version' => 0, 'state' => 1, 'vadmin' => $admin, 'vtime' => $curtime, 'vtype' => $accountUnit['vtype'], 'app_id' => APP_ID, 'op_type' => 7]);

            $conn->commit();
            $msgs = [
                'cmd'  => 'system.message',
                'data' => ['from' => SystemNotifyUid, 'uid' => $data['uid'], 'message' => $reason]
            ];
        } catch (\Exception $e) {
            $conn->rollback();
            Helper::releaseLock($lockKey);
            if ($e instanceof ApiException) {
                throw $e;
            }
            return [false, $e->getMessage()];
        }
        Helper::releaseLock($lockKey);

        $msgs && NsqClient::publish(NsqConstant::TOPIC_XS_ADMIN, $msgs);

        /*【越南】
        【罚款、冻结、指定罚款 】
        发送通知*/
        $userArea = XsUserBigarea::findFirst($data['uid']);
        if (in_array($type, [1, 3, 4,]) && $userArea && $userArea->bigarea_id == 7) {
            $user = XsUserProfile::findFirst($data['uid']);
            $headmsg = XsPayPunishLog::TYPE_MAP[$type]['text'];
            $moneyDebts = 0;    //log表本函数未插入数据默认0
            $tmpM = XsUserMoney::useMaster()::findFirst($data['uid']);
            if ($tmpM) $moneyDebts = ($tmpM->money_debts) / 100;
            $dateline = ($curtime > 0) ? date('Y-m-d H:i', $curtime) : '-';
            $bodymsg = ($data['uid']) . '，' . ($user ? $user->name : '-') . '，' . ($headmsg) . '' . ($money / 100) . '币，当前欠款金额' . $moneyDebts . '元，' . ($data['mark'] ?? '') . '，' . $dateline;


            $obj = factory_single_obj(SdkSlack::class);
            $obj->sendMsg((ENV == 'dev') ? '' : '',
                'text', '【' . $headmsg . '】' . $bodymsg);
        }

        return [true, ''];
    }
}
