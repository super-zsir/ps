<?php


namespace Imee\Service\Operate\Coupon;


use Imee\Comp\Common\Log\Service\OperateLog;
use Imee\Comp\Operate\Auth\Models\Cms\CmsUser;
use Imee\Exception\ApiException;
use Imee\Models\Xs\XsAgencyHunterGiftBag;
use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xsst\BaseModel;
use Imee\Models\Xsst\BmsOperateLog;
use Imee\Models\Xsst\XsstCouponAreaLog;
use Imee\Models\Xs\XsstCouponAreaManage;
use Imee\Models\Xs\XsstCouponIssued;
use Imee\Models\Xs\XsUserBigarea;
use Imee\Models\Xs\XsUserProfile;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;
use Imee\Service\StatusService;
use Phalcon\Di;

class GameCouponIssuedService
{

    /** @var PsService $psServer */
    private $psServer;

    public function __construct()
    {
        $this->psServer = new PsService();
    }

    public function getListAndTotal($params): array
    {
        $page = (int)array_get($params, 'page', 1);
        $limit = (int)array_get($params, 'limit', 15);

        $filter = [];
        $bigareaId = intval(array_get($params, 'bigarea_id', 0));
        $bigareaId && $filter[] = ['bigarea_id', '=', $bigareaId];
        $uidBigareaId = intval(array_get($params, 'uid_bigarea_id', 0));
        $uidBigareaId && $filter[] = ['uid_bigarea_id', '=', $uidBigareaId];
        $uid = intval(array_get($params, 'uid', 0));
        $uid && $filter[] = ['uid', '=', $uid];
        $auditStatus = intval(array_get($params, 'audit_status', 0));
        $auditStatus && $filter[] = ['audit_status', '=', $auditStatus];


        $data = XsstCouponIssued::getListAndTotal($filter, '*', 'id desc', $page, $limit);

        $uidList = array_unique(array_column($data['data'], 'uid'));
        $uidList = XsUserProfile::getListByWhere([['uid', 'in', array_values($uidList)]], 'uid, name');
        $allUser = array_column($uidList, 'name', 'uid');

        $adminIdList = array_merge(array_unique(array_column($data['data'], 'created_id')),
            array_unique(array_column($data['data'], 'audit_id')));
        $adminList = CmsUser::getListByWhere([['user_id', 'in', array_values(array_unique($adminIdList))]], 'user_id, user_name');
        $allAdmin = array_column($adminList, 'user_name', 'user_id');

        foreach ($data['data'] as &$rec) {
            $_uid = array_get($rec, 'uid', 0);
            $_createdId = array_get($rec, 'created_id', 0);
            $_createdAt = array_get($rec, 'created_at', 0);
            $_auditId = array_get($rec, 'audit_id', 0);
            $_auditAt = array_get($rec, 'audit_at', 0);
            $_expire = array_get($rec, 'expire_time', 0);

            $rec['audit_show'] = $rec['audit_status'] == XsstCouponIssued::AUDIT_WAIT && $rec['batch_id'] == 0 ? 2 : 1;
            $rec['user_name'] = array_get($allUser, $_uid, '');
            $rec['created_name'] = array_get($allAdmin, $_createdId, '');
            $rec['created_at'] = $_createdAt ? date('Y-m-d H:i:s', $_createdAt) : '';
            $rec['audit_name'] = array_get($allAdmin, $_auditId, '');
            $rec['audit_at'] = $_auditAt ? date('Y-m-d H:i:s', $_auditAt) : '';
            $rec['expire_time'] = $_expire ? date('Y-m-d H:i:s', $_expire) : '-';
        }
        return $data;
    }

    public function add($params): array
    {
        $bigareaId = intval(array_get($params, 'bigarea_id', 0));
        $uid = intval(array_get($params, 'uid', 0));
        $couponId = intval(array_get($params, 'coupon_id', 0));//
        $num = intval(array_get($params, 'num', 0));
        $expire = intval(array_get($params, 'expire_time', 0));
        $note = trim(array_get($params, 'note', ''));
        $adminId = intval(array_get($params, 'admin_id', 0));
        $agbId = intval(array_get($params, 'agb_id', 0));

        list($amountFlg, $amount) = $this->getCouponAmount($couponId);
        if (!$amountFlg) {
            return [$amountFlg, $amount];
        }

        $userProfile = XsUserProfile::findOne($uid);
        if (empty($userProfile)) {
            return [false, '找不到uid'];
        }

        $userBigarea = XsUserBigarea::findOne($uid);
        if (empty($userBigarea)) {
            return [false, '找不到用户所属大区'];
        }

        if ($userBigarea['bigarea_id'] != $bigareaId) {
            return [false, '用户所属大区与下发大区不一致'];
        }

        $price = $amount * $num;
        $waitPrice = XsstCouponIssued::useMaster()::getListByWhere([
            ['bigarea_id', '=', $bigareaId], ['audit_status', '=', XsstCouponIssued::AUDIT_WAIT]
        ], 'SUM(price) as price');
        $waitPrice = empty($waitPrice) ? 0 : intval($waitPrice[0]['price']);

        $couponAreaManage = XsstCouponAreaManage::findOneByWhere([['bigarea_id', '=', $bigareaId]]);
        if ($couponAreaManage['amount'] < $price + $waitPrice) {
            return [false, '当前大区可用余额不足！'];
        }

        $expireTime = $this->getExpireTime($expire);

        $data = [
            'issued_type' => XsstCouponIssued::ISSUED_TYPE,
            'uid' => $uid,
            'uid_bigarea_id' => array_get($userBigarea, 'bigarea_id', 0),
            'coupon_id' => $couponId,
            'amount' => $amount,
            'num' => $num,
            'expire_time' => $expireTime,
            'price' => $price,
            'agb_id' => $agbId,
            'bigarea_id' => $bigareaId,
            'note' => $note,
            'audit_status' => XsstCouponIssued::AUDIT_WAIT,
            'created_id' => $adminId,
            'created_at' => time()
        ];

        list($flg, $rec) = XsstCouponIssued::add($data);

        return [$flg, $flg ? ['id' => $rec, 'before_json' => [], 'after_json' => $data] : $rec];
    }

    public function addBatch($params, $data): array
    {
        if (!empty($data) && !is_numeric(array_get($data[0], 'bigarea_id'))) {
            unset($data[0]);
        }
        $amountList = $this->psServer->getGameCouponAllList();
        $amountList = array_column($amountList, 'amount', 'id');

        $thisWeek = $this->getExpireTime(XsstCouponIssued::PERIOD_THIS_WEEK);
        $nextWeek = $this->getExpireTime(XsstCouponIssued::PERIOD_NEXT_WEEK);

        $adminId = intval(array_get($params, 'admin_id', 0));


        $uidList = array_values(array_unique(array_column($data, 'uid')));
        $allUserBigArea = [];

        //校验用户uid是否正常
        foreach ($uidList as $uid) {
            $userProfile = XsUserProfile::findOne($uid);
            if (empty($userProfile)) {
                return [false, '找不到uid:' . $uid];
            }

            $userBigarea = XsUserBigarea::findOne($uid);
            if (empty($userBigarea)) {
                return [false, '找不到uid:' . $uid . '所属大区'];
            }
            $allUserBigArea[$uid] = array_get($userBigarea, 'bigarea_id', 0);
        }

        //校验各个大区余额是否充足
        $totalPrice = [];
        foreach ($data as $item) {
            $userBigArea = $allUserBigArea[$item['uid']] ?? 0;
            if ($userBigArea != $item['bigarea_id']) {
                return [false, 'uid:' . $item['uid'] . '所属大区与下发大区不一致'];
            }
            $amount = (int)array_get($amountList, $item['coupon_id'], 0);
            if (!isset($totalPrice[$item['bigarea_id']])) {
                $totalPrice[$item['bigarea_id']] = 0;
            }
            $totalPrice[$item['bigarea_id']] += (int)$item['num'] * $amount;
        }
        foreach ($totalPrice as $k => $totalPriceItem) {
            $waitPrice = XsstCouponIssued::getListByWhere([
                ['bigarea_id', '=', $k], ['audit_status', '=', XsstCouponIssued::AUDIT_WAIT]
            ], 'SUM(price) as price');
            $waitPrice = empty($waitPrice) ? 0 : intval($waitPrice[0]['price']);

            $couponAreaManage = XsstCouponAreaManage::findOneByWhere([['bigarea_id', '=', $k]]);
            if ($couponAreaManage['amount'] < $totalPriceItem + $waitPrice) {
                return [false, '当前大区可用余额不足！'];
            }
        }

        //批量导入新增数据
        $insertList = [];
        foreach ($data as $item) {
            $amount = (int)array_get($amountList, $item['coupon_id'], 0);
            $insertList[] = [
                'issued_type' => XsstCouponIssued::ISSUED_TYPE,
                'uid' => $item['uid'],
                'uid_bigarea_id' => array_get($allUserBigArea, $item['uid'], 0),
                'coupon_id' => $item['coupon_id'],
                'amount' => $amount,
                'num' => (int)$item['num'],
                'expire_time' => ($item['expire_time'] == XsstCouponIssued::PERIOD_THIS_WEEK) ? $thisWeek : $nextWeek,
                'price' => $amount * $item['num'],
                'bigarea_id' => $item['bigarea_id'],
                'note' => array_get($item, 'note', ''),
                'audit_status' => XsstCouponIssued::AUDIT_WAIT,
                'created_id' => $adminId,
                'created_at' => time()
            ];
        }
        list($flg, $rec) = XsstCouponIssued::addBatch($insertList);

        return [$flg, $flg ? ['after_json' => $insertList] : $rec];
    }

    public function sub($params): array
    {
        $adminId = intval(array_get($params, 'admin_id', 0));
        $uid = intval(array_get($params, 'uid', 0));
        $couponId = intval(array_get($params, 'coupon_id', 0));
        $num = intval(array_get($params, 'num', 0));
        $note = trim(array_get($params, 'note', ''));


        $userProfile = XsUserProfile::findOne($uid);
        if (empty($userProfile)) {
            return [false, '找不到uid'];
        }

        $userBigarea = XsUserBigarea::findOne($uid);
        if (empty($userBigarea)) {
            return [false, '找不到用户所属大区'];
        }

        list($amountFlg, $amount) = $this->getCouponAmount($couponId);
        if (!$amountFlg) {
            return [$amountFlg, $amount];
        }

        $price = $amount * $num;
        $data = [
            'issued_type' => XsstCouponIssued::ISSUED_TYPE_SUB,
            'uid' => $uid,
            'uid_bigarea_id' => array_get($userBigarea, 'bigarea_id', 0),
            'coupon_id' => $couponId,
            'amount' => $amount,
            'num' => $num,
            'price' => $price,
            'note' => $note,
            'audit_status' => XsstCouponIssued::AUDIT_SUCCESS, //扣除直接生效
            'created_id' => $adminId,
            'created_at' => time()
        ];

        list($flg, $rec) = XsstCouponIssued::add($data);

        if ($flg) {
            list($psFlg, $reason) = $this->psServer->gameCouponIssuedOp([
                'uid' => $uid,
                'num' => $num,
                'coupon_id' => $couponId,
                'op' => 2,//接口传值  1代表下发，2代表扣除
                'order_id' => $rec,
                'reason' => $note,
            ]);
            if (!$psFlg) {
                XsstCouponIssued::deleteById($rec);
                return [$psFlg, $reason];
            }
        }
        return [$flg, $flg ? ['id' => $rec, 'before_json' => [], 'after_json' => $data] : $rec];
    }

    public function audit($params): array
    {
        $id = intval(array_get($params, 'id', 0));
        $status = intval(array_get($params, 'status', 0));
        $auditId = intval(array_get($params, 'admin_id', 0));

        $couponIssued = XsstCouponIssued::findOne($id);
        if (empty($couponIssued)) {
            return [false, '审核失败,数据有误'];
        }
        if ($couponIssued['batch_id'] > 0) {
            return [false, '批量下发的不能在此审批，请前往“礼包下发-批量任务记录”审批'];
        }

        // 修改后审核状态相同或者修改前状态不是待审核直接返回
        if (($status == $couponIssued['audit_status']) ||
            ($couponIssued['audit_status'] != XsstCouponIssued::AUDIT_WAIT)) {
            return [true, ''];
        }
        if ($status == XsstCouponIssued::AUDIT_SUCCESS && $couponIssued['expire_time'] < time() && empty($couponIssued['agb_id'])) {
            return [false, '审核失败,数据已过期'];
        }

        if (!in_array($status, [XsstCouponIssued::AUDIT_REFUSE, XsstCouponIssued::AUDIT_SUCCESS])) {
            return [false, sprintf('扣减失败，状态值【%d】错误', $status)];
        }

        $bigareaId = array_get($couponIssued, 'bigarea_id', 0);
        $price = (int)array_get($couponIssued, 'price', 0);
        $couponAreaManage = XsstCouponAreaManage::findOneByWhere([['bigarea_id', '=', $bigareaId]]);

        if (array_get($couponAreaManage, 'amount', 0) < $price) {
            $status = XsstCouponIssued::AUDIT_FAIL;
        }

        $flg = true;
        $rec = '';
        if ($status == XsstCouponIssued::AUDIT_SUCCESS) {
            // 礼包相关优惠券审批通过之后不需要走CouponOperate的逻辑
            if ($couponIssued['agb_id'] == 0) {
                $data = [
                    'uid' => (int)array_get($couponIssued, 'uid', 0),
                    'num' => (int)array_get($couponIssued, 'num', 0),
                    'expire_time' => (int)array_get($couponIssued, 'expire_time', 0),
                    'coupon_id' => (int)array_get($couponIssued, 'coupon_id', 0),
                    'op' => 1,//接口传值  1代表下发，2代表扣除
                    'order_id' => $id,
                    'reason' => array_get($couponIssued, 'note', 0),
                ];
                list($flg, $rec) = $this->psServer->gameCouponIssuedOp($data);
            }
            //审核成功，扣除大区价值，并且记录日志
            if ($flg) {
                XsstCouponAreaManage::changeCouponAreaBalance([
                    'id' => (int)array_get($couponAreaManage, 'id', 0),
                    'type' => XsstCouponAreaLog::TYPE_SEND,
                    'amount' => $price,
                    'operator' => $auditId
                ], true);
            }
        }

        $passData = [
            'audit_status' => $status,
            'audit_id' => $auditId,
            'audit_at' => time()
        ];
        $flg && XsstCouponIssued::updateByWhere([['id', '=', $id]], $passData);

        // agb_id不为空时，表示当前优惠券下发数据为礼包下发中产生。需要同步更新礼包下发中的审核状态
        if ($couponIssued['agb_id'] != 0) {
            $this->updateAgencyHunterGiftBagStatus($couponIssued['agb_id'], $status);
        }
        return [$flg, $flg ? ['after_json' => $passData] : $rec];
    }

    public function auditBatch($params): array
    {
        $idArr = Helper::formatIdString($params['id']);
        $data = [
            'status' => $params['status'] ?? 0,
            'admin_id' => $params['admin_id']
        ];
        $successId = [];
        foreach ($idArr as $id) {
            list($res, $msg) = $this->audit(array_merge($data, ['id' => $id]));
            if (!$res) {
                return [false, 'ID：' .$id . '审核失败，失败原因：' . $msg];
            }
            $msg && $successId[] = $id;
        }
        return [true, [
            'id' => $successId,
            'after_json' => ['audit_status' => $data['status'], 'audit_id' => $data['admin_id']],
        ]];
    }

    public function config(): array
    {
        $list = XsBigarea::getAllNewBigArea();

        $bigareaArr = [];
        foreach ($list as $k => $v) {
            $bigareaArr[] = [
                'label' => $v . '（ID:' . $k . '）',
                'value' => $k
            ];
        }

        $bigareaAmount = XsstCouponAreaManage::getListByWhere([], 'bigarea_id, amount');
        foreach ($bigareaAmount as &$item) {
            $waitPrice = XsstCouponIssued::getListByWhere([
                ['bigarea_id', '=', $item['bigarea_id']], ['audit_status', '=', XsstCouponIssued::AUDIT_WAIT]
            ], 'SUM(price) as price');
            $waitPrice = empty($waitPrice) ? 0 : intval($waitPrice[0]['price']);

            $item['amount'] = max($item['amount'] - $waitPrice, 0);
        }

        $couponArr = self::getGameCouponAllList();
        $expireArr = [];
        foreach (XsstCouponIssued::$expire as $k => $v) {
            $expireArr[] = [
                'label' => $v,
                'value' => $k
            ];
        }


        return compact('bigareaArr', 'bigareaAmount', 'couponArr', 'expireArr');
    }

    public static function getGameCouponAllList()
    {
        $data = (new PsService())->getGameCouponAllList();
        $couponArr = [];
        foreach ($data as $rec) {
            $couponArr[] = [
                'label' => 'id: ' . $rec['id'] . ' (' . $rec['amount'] . '💎)',
                'value' => (string) $rec['id']
            ];
        }

        return $couponArr;
    }

    public static function getCouponIssuedAuditStatus($value = null, string $format = '')
    {
        $map = XsstCouponIssued::$auditStatus;
        if (!empty($value)) {
            return $map[$value] ?? '';
        }
        if (!empty($format)) {
            $map = StatusService::formatMap($map, $format);
        }
        return $map;
    }

    public static function getCouponIssuedPeriod($value = null, string $format = '')
    {
        $map = XsstCouponIssued::$expire;
        if (!empty($value)) {
            return $map[$value] ?? '';
        }
        if (!empty($format)) {
            $map = StatusService::formatMap($map, $format);
        }
        return $map;
    }

    public static function getCouponIssuedType($value = null, string $format = '')
    {
        $map = XsstCouponIssued::$types;
        if (!empty($value)) {
            return $map[$value] ?? '';
        }
        if (!empty($format)) {
            $map = StatusService::formatMap($map, $format);
        }
        return $map;
    }

    private function getExpireTime($period)
    {
        $first = 1;
        //当前日期
        $defaultDate = date('Y-m-d');
        //获取当前周的第几天，周日是0，周一到周六是1-6
        $w = date('w', strtotime($defaultDate));
        //获取本周开始时间，如果$w是0 ，则表示周日，减去6天
        $weekStart = date('Y-m-d', strtotime("$defaultDate -" . ($w ? $w - $first : 6) . ' days'));

        $periodTime = 0;
        switch ($period) {
            case XsstCouponIssued::PERIOD_THIS_WEEK:
                $weekEnd = date('Y-m-d', strtotime("$weekStart +6 days")) . ' 23:59:59';
                $periodTime = strtotime($weekEnd);
                break;
            case XsstCouponIssued::PERIOD_NEXT_WEEK:
                $weekEnd = date('Y-m-d', strtotime("$weekStart +13 days")) . ' 23:59:59';
                $periodTime = strtotime($weekEnd);
                break;
        }
        return $periodTime;
    }

    private function updateAgencyHunterGiftBagStatus(int $id, int $status)
    {
        // 礼包中不存在此数据或者不是审核中状态直接返回
        $giftBagInfo = XsAgencyHunterGiftBag::findOne($id);
        if (empty($giftBagInfo) || $giftBagInfo['status'] != XsAgencyHunterGiftBag::HAVE_AUDIT_STATUS) {
            return;
        }
        // 转一下审核状态 礼包下发中审核状态成功：2 失败：3
        if ($status == XsstCouponIssued::AUDIT_SUCCESS) {
            $auditStatus = XsAgencyHunterGiftBag::SUCCESS_AUDIT_STATUS;
        } else {
            $auditStatus = XsAgencyHunterGiftBag::ERROR_AUDIT_STATUS;
        }
        list($res, $msg) = $this->psServer->updateAgencyHunterGiftBagStatus($id, $auditStatus);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
        $userInfo = Helper::getSystemUserInfo();
        OperateLog::addOperateLog([
            'uid'          => $giftBagInfo['uid'],
            'model_id'     => $id,
            'model'        => 'welcomehuntergift',
            'action'       => BmsOperateLog::ACTION_UPDATE,
            'content'      => '更新礼包审核状态',
            'before_json'  => ['status' => XsAgencyHunterGiftBag::HAVE_AUDIT_STATUS],
            'after_json'   => ['status' => $auditStatus],
            'operate_id'   => $userInfo['user_id'],
            'operate_name' => $userInfo['user_name'],
        ]);

    }

    public function getCouponAmount($couponId): array
    {
        try {
            $data = $this->psServer->getGameCouponList(['id' => $couponId]);
            return [true, $data['data'][0]['amount']];
        } catch (\Exception $e) {
            return [false, '优惠券ID异常，找不到档位'];
        }
    }

    /**
     * 添加加审核一起处理
     * @param array $params
     * @return void
     * @throws ApiException
     */
    public function addAndAudit(array $params): void
    {
        $uidList = $params['uid_list'] ?? [];
        if (empty($uidList)) {
            throw new ApiException(ApiException::MSG_ERROR, 'uid_list not null');
        }
        $auditId = [];
        // 保证同一批数据必须全部添加成功或者失败
        $conn = Di::getDefault()->getShared(BaseModel::SCHEMA);
        $conn->begin();
        try {
            foreach ($uidList as $uid) {
                $params['uid'] = $uid;
                list($res, $data) = $this->add($params);
                if (!$res) {
                    throw new ApiException(ApiException::MSG_ERROR, $data);
                }
                $auditId[] = $data['id'];
            }
            $conn->commit();
        } catch (ApiException $e) {
            $conn->rollback();
            throw new ApiException(ApiException::MSG_ERROR, $e->getMsg());
        }

        usleep(1000 * 100);
        if ($auditId) {
            $data = [
                'id'       => implode(',', $auditId),
                'status'   => XsstCouponIssued::AUDIT_SUCCESS,
                'admin_id' => $params['admin_id']
            ];
            list($auditRes, $auditMsg) = $this->auditBatch($data);
            if (!$auditRes) {
                throw new ApiException(ApiException::MSG_ERROR, $auditMsg);
            }
        }
    }
}