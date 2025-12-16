<?php


namespace Imee\Models\Xs;


class XsstCouponIssued extends BaseModel
{
    public static $primaryKey = 'id';

    const PERIOD_THIS_WEEK = 1;
    const PERIOD_NEXT_WEEK = 2;

    public static $expire = [
        self::PERIOD_THIS_WEEK => '本周失效',
        self::PERIOD_NEXT_WEEK => '下周失效',
    ];

    const AUDIT_WAIT = 1;
    const AUDIT_REFUSE = 2;
    const AUDIT_SUCCESS = 3;
    const AUDIT_FAIL = 4;

    public static $auditStatus = [
        self::AUDIT_WAIT => '待审核',
        self::AUDIT_REFUSE => '已拒绝',
        self::AUDIT_SUCCESS => '已生效',
        self::AUDIT_FAIL => '失败，余额不足',
    ];

    const ISSUED_TYPE = 1;
    const ISSUED_TYPE_SUB = 2;
    public static $types = [
        self::ISSUED_TYPE => '下发',
        self::ISSUED_TYPE_SUB => '扣除',
    ];


    public static function uploadFields(): array
    {
        return [
            'bigarea_id' => '优惠券大区ID',
            'uid' => '用户ID',
            'coupon_id' => '游戏优惠券id',
            'num' => '数量',
            'expire_time' => '有效期（1本周，2下周）',
            'note' => '备注',
        ];
    }

    /**
     * 根据礼包下发ID获取优惠券下发ID
     * @param array $agbIds
     * @return array
     */
    public static function getListByAgbId(array $agbIds): array
    {
        if (empty($agbIds)) {
            return [];
        }

        $list = self::getListByWhere([
            ['agb_id', 'IN', $agbIds]
        ], 'id, agb_id');

        return $list ? array_column($list, 'id', 'agb_id') : [];
    }

    /**
     * 获取大区待审核金额
     * @param array $bigAreaArr
     * @return array
     */
    public static function getBigAreaWaitPriceList(array $bigAreaArr): array
    {
        if (empty($bigAreaArr)) {
            return [];
        }

        $list = self::getListByWhere([
            ['bigarea_id', 'IN', $bigAreaArr],
            ['audit_status', '=', self::AUDIT_WAIT]
        ], 'SUM(price) as price, bigarea_id', 'bigarea_id desc', 0, 0, 'bigarea_id');

        return $list ? array_column($list, 'price', 'bigarea_id') : [];
    }

}