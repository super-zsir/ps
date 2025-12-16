<?php


namespace Imee\Models\Xs;

use Imee\Models\Xsst\XsstCouponAreaLog;
use Imee\Service\Helper;

class XsstCouponAreaManage extends BaseModel
{
    public static $primaryKey = 'id';

    /**
     * 修改大区账户余额，记录余额日志
     * @param $data
     * @param $isCouponAudit
     * @return array
     */
    public static function changeCouponAreaBalance($data, $isCouponAudit = false): array
    {
        // 优惠券审核业务钱以扣，不需要重复扣
        if ($isCouponAudit) {
            return [true, ''];
        }

        $id = $data['id'];
        $type = $data['type'];
        $amount = $data['amount'];
        $operator = $data['operator'];
        $doc = array_get($data, 'doc', '');
        $note = array_get($data, 'note', '');

        if (!is_numeric($id) || !in_array($type, array_keys(XsstCouponAreaLog::$types))
            || !is_numeric($amount) || $amount <= 0 || !is_numeric($operator)) {
            return [false, '参数错误'];
        }

        if ($type == XsstCouponAreaLog::TYPE_ADD && !$doc) {
            return [false, '增加余额，请填写oa单号'];
        }

        if ($type == XsstCouponAreaLog::TYPE_SUB && !$note) {
            return [false, '扣减余额，请填写备注'];
        }

        if (in_array($type, [XsstCouponAreaLog::TYPE_SUB, XsstCouponAreaLog::TYPE_SEND])) {
            $amount = -$amount;
        }

        $repeat = 15;
        while ($repeat-- > 0) {

            $couponAreaManage = XsstCouponAreaManage::findOne($data['id']);
            if (empty($couponAreaManage)) {
                return [false, '数据错误'];
            }

            $version = array_get($couponAreaManage, 'version', 0);
            $beforeAmount = array_get($couponAreaManage, 'amount', 0);
            $afterAmount = $beforeAmount + $amount;
            if ($afterAmount < 0) {
                return [false, '大区账户余额不足，无法扣除'];
            }
            list($flg, ,) = XsstCouponAreaManage::updateByWhere([
                ['id', '=', $id], ['version', '=', $version],
            ], ['amount' => $afterAmount, 'dateline' => time(), 'operator' => $operator]);

            if ($flg) {
                //记录 大区账户余额  变更日志
                XsstCouponAreaLog::add([
                    'bigarea_id' => $couponAreaManage['bigarea_id'],
                    'type' => $type,
                    'amount' => abs($amount),
                    'before_amount' => $beforeAmount,
                    'after_amount' => $afterAmount,
                    'doc' => $doc,
                    'note' => $note,
                    'dateline' => time(),
                    'operator' => $operator
                ]);
                return [true, ''];
            }
        }

        return [false, '大区账户余额变更错误，请稍后尝试...'];
    }

    /**
     * 获取大区余额
     * @param array $bigAreaIdArr
     * @return array
     */
    public static function getListByBigArea(array $bigAreaIdArr): array
    {
        $list = self::getListByWhere([
            ['bigarea_id', 'IN', $bigAreaIdArr]
        ], 'bigarea_id, amount');

        return $list ? array_column($list, 'amount', 'bigarea_id') : [];
    }
}