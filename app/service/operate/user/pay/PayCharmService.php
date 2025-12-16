<?php

namespace Imee\Service\Operate\User\Pay;

use Imee\Comp\Gid\Models\Xs\XsPay;
use Imee\Models\recharge\XsIapConfig;
use Imee\Models\Xs\XsCommodity;
use Imee\Models\Xs\XsGift;
use Imee\Models\Xs\XsLuckyGiftDivided;
use Imee\Models\Xs\XsOrder;
use Imee\Models\Xs\XsPayChangeNew;
use Imee\Service\Helper;
use Imee\Models\Xs\BaseModel as XsBaseModel;

/**
 * 用户账户变化历史【钻石魅力值】类型
 */
class PayCharmService extends PayHistoryAbstractService
{
    /**
     * 获取钻石魅力值列表
     * @return array
     */
    public function getPayHistoryList(): array
    {
        $result = array('data' => [], 'total' => 0);
        if (empty($this->conditions)) {
            return $result;
        }
        $conditions = ' where ' . $this->conditions;
        $total = Helper::fetchOne('select count(*) as num from xs_pay_change_new force index(uid_2) ' . $conditions, null, XsBaseModel::SCHEMA_READ);
        $total = intval($total['num']);
        if ($total == 0) {
            return $result;
        }
        $query = sprintf('select * from xs_pay_change_new force index(uid_2) %s order by %s limit %s,%s', $conditions, $this->query['order'], $this->query['offset'], $this->query['limit']);
        $data = Helper::fetch($query, null, XsBaseModel::SCHEMA_READ);
        /*$total = intval(\XsPayChangeNew::count($this->conditions));
        if ($total === 0) {
            return $result;
        }
        $data = \XsPayChangeNew::find(array_merge([$this->conditions], $this->query))->toArray();*/
        if (empty($data)) {
            return $result;
        }
        $payIds = array();

        foreach ($data as &$val) {
            $val['dateline'] = date('Y-m-d H:i:s', $val['dateline']);
            $val['reason'] = @unserialize($val['reason']) ?: @json_decode($val['reason'], true);
            $val['reason_person'] = highlight_string(var_export($val['reason'], true), true);
            $val['reason_display'] = $val['subject'];
            $val['extra'] = '';
            if (!empty($val['reason'])) {
                switch ($val['op']) {
                    case 'pay':
                        $val['to'] = $val['reason']['id'];
                        $val['_sign'] = true;
                        $payIds[] = intval($val['to']);
                        break;
                    case 'consume':
                        $val = self::getConsume($val); // 消费
                        break;
                    case 'income':
                        $val = self::getIncome($val); // 收入
                        break;
                    case 'extend':
                        $val = self::getExtend($val); // 扩展类型（包含直播打赏）
                        break;
                }
            }
            $val['to'] = $val['to'] ?? ' - ';
            $val['rid'] = $val['reason']['rid'] ?? '';
            $val['op'] = XsPayChangeNew::OP_REASON[$val['op']] ?? $val['op'];
        }
        if (empty($payIds)) {
            return ['data' => $data, 'total' => $total];
        }
        $pays = XsPay::getBatchCommon($payIds, ['id', 'product_id', 'platform']);
        $iapIds = Helper::arrayFilter($pays, 'product_id');
        $iaps = XsIapConfig::getBatchCommon($iapIds, ['product_id', 'id']);

        foreach ($data as &$val) {
            if (!isset($val['_sign']) || $val['_sign'] !== true) {
                continue;
            }
            $pid = intval($val['to']);
            $payPlatform = $pays[$pid]['platform'] ?? '';
            $productId = $iaps[$pays[$pid]['product_id'] ?? 0]['product_id'] ?? '';

            if ($payPlatform == 'fomo') {
                $tmp = explode(".", $productId);
                array_pop($tmp);
                $platform = implode(".", $tmp);
                if ($platform == 'fomo') $platform = 'fomo.wechat';
            } else {
                $platform = $payPlatform;
            }
            $val['extra'] = $platform;
        }
        return ['data' => $data, 'total' => $total];
    }


    /**
     * 获取消费数据
     * @param $val
     * @return array
     */
    private static function getConsume($val): array
    {
        $type = $val['reason']['type'] ?? '';
        if (empty($type)) {
            return $val;
        }

        // 邀约单
        if ($type == 'order' && isset($val['reason']['id'])) {
            $rec = XsOrder::findFirst([
                'conditions' => "id = :id:",
                'bind'       => ['id' => (int)$val['reason']['id']]
            ]);
            $val['to'] = !empty($rec) ? $rec->to : '';
        }
        if (in_array($val['reason']['type'], array('package', 'chat-gift', 'chat-coin', 'defend', 'shop-buy'))) {
            $val['to'] = $val['reason']['to'] ?? '';
        }

        // greedy账单
        if ($type == 'greedy-consume') {
            $val['op'] = XsPayChangeNew::OP_REASON[$val['op']] . '-Greedy';
        }

        // 商城账单
        if (in_array($type, array('coin-shop-buy', 'shop-buy')) && isset($val['reason']['cid'])) {
            $rec = XsCommodity::findFirst([
                'conditions' => "cid = :id:",
                'bind'       => ['id' => (int)$val['reason']['cid']]
            ]);
            $val['op'] = XsPayChangeNew::OP_REASON[$val['op']] . '-购买道具';
            $val['property_id'] = $val['reason']['cid'];
            $val['property_name'] = !empty($rec) ? $rec->name : '';
            $val['property_discount'] = $val['reason']['duction_money'] ?? 0;
            $val['property_price'] = !empty($rec) ? $rec->price : '0'; // 单位：钻石
            $val['property_num'] = $val['reason']['num'];
        }
        // 礼物账单
        return self::getGiftOrder($val, '-送礼');
    }


    /**
     * 获取收入类型数据
     * @param $val
     * @return array
     */
    private static function getIncome($val): array
    {
        $val['to'] = $val['reason']['from'];
        if ($val['reason']['type'] == 'greedy-add') {
            $val['op'] = XsPayChangeNew::OP_REASON[$val['op']] . '-Greedy';
        }
        return self::getGiftOrder($val, '-收礼');
    }


    /**
     * 获取扩展类型数据，包括直播打赏
     * @param $val
     * @return array
     */
    private static function getExtend($val): array
    {
        if (!in_array($val['reason']['type'], ['BILL_TYPE_LIVE_ROOM_GIFT_GIVE', 'BILL_TYPE_LIVE_ROOM_GIFT_RECEIVE'])) {
            if ($val['reason']['type'] == 'BILL_TYPE_UNLOCK_DIAMOND') { // 锁定金额解锁
                $val['to'] = $val['reason']['id'] ?? '';
            }
            return $val;
        }
        if ($val['reason']['type'] == 'BILL_TYPE_LIVE_ROOM_GIFT_GIVE') {
            $val['op'] = '直播打赏 -送礼';
            $val['to'] = $val['reason']['to'] ?? '-';
        } else {
            $val['op'] = '直播打赏 -收礼';
            $val['to'] = $val['reason']['from'] ?? '-';
        }
        $id = $val['reason']['gift_id'] ?? 0;
        if (empty($id)) {
            return $val;
        }
        $rec = XsGift::findFirst([
            'conditions' => "id = :id:",
            'bind'       => ['id' => $id]
        ]);
        $val['git_id'] = $id;
        $val['git_name'] = !empty($rec) ? $rec->name : '';
        $val['git_discount'] = 0;
        $val['git_price'] = !empty($rec) ? 100 * ($rec->price) : '0'; // 伴伴币转钻石1:100
        $val['git_num'] = $val['reason']['num'];
        // 判断幸运礼物
        $val['is_lucky'] = !empty($rec) && intval($rec->is_lucky) == 1 ? '是' : '否';
        $val['lucky_divided'] = '';
        if (!empty($rec) && $rec->is_lucky == 1) {
            $lucky = XsLuckyGiftDivided::findFirst([
                'conditions' => "gift_id = :gift_id: and is_delete=0",
                'bind'       => ['gift_id' => $id]
            ]);
            if (!empty($lucky)) {
                $val['lucky_divided'] = strval($lucky->proportion);
            }
        }
        return $val;
    }


    /**
     * 返回礼物账单基本信息，即礼物ID、礼物名称、单价（钻石）、数量、折扣（钻石）字段
     * @param $val
     * @param $giftOp
     * @return array
     */
    private static function getGiftOrder($val, $giftOp): array
    {
        $type = $val['reason']['type'] ?? '';
        if (empty($type) || !(isset($val['reason']['gid']) || isset($val['reason']['_id']))) {
            return $val;
        }
        $giftType = array('package', 'game-package', 'package-coin', 'chat-gift', 'chat-coin', 'chat-bean');
        if (!in_array($type, $giftType)) {
            return $val;
        }
        $val['op'] = XsPayChangeNew::OP_REASON[$val['op']] . $giftOp;
        $id = $val['reason']['gid'] ?? $val['reason']['_id'];
        $rec = XsGift::findFirst([
            'conditions' => "id = :id:",
            'bind'       => ['id' => $id]
        ]);
        $val['git_id'] = $id;
        $val['git_name'] = !empty($rec) ? $rec->name : '';
        $val['git_discount'] = $val['reason']['duction_money'] ?? 0;
        $val['git_price'] = !empty($rec) ? 100 * ($rec->price) : '0';
        $val['git_num'] = $val['reason']['_num'];
        $val['is_lucky'] = intval($rec->is_lucky) == 1 ? '是' : '否';
        $val['lucky_divided'] = '';
        // 判断幸运礼物
        if (!empty($rec) && $rec->is_lucky == 1) {
            $lucky = XsLuckyGiftDivided::findFirst([
                'conditions' => "gift_id = :gift_id: and is_delete=0",
                'bind'       => ['gift_id' => $id]
            ]);
            if (!empty($lucky)) {
                $val['lucky_divided'] = strval($lucky->proportion);
            }
        }
        return $val;
    }
}