<?php


namespace Imee\Service\Operate\Coupon;


use Imee\Models\Xsst\XsstCouponAreaLog;
use Imee\Models\Xs\XsstCouponAreaManage;
use Imee\Service\Helper;

class CouponAreaManageService
{

    public function getListAndTotal($params): array
    {
        $page = (int)array_get($params, 'page', 1);
        $limit = (int)array_get($params, 'limit', 15);

        $filter = [];
        $bigareaId = intval(array_get($params, 'bigarea_id', 0));
        $bigareaId && $filter[] = ['bigarea_id', '=', $bigareaId];

        $data = XsstCouponAreaManage::getListAndTotal($filter, '*', 'id desc', $page, $limit);

        foreach ($data['data'] as &$rec) {
            $rec['operator'] = Helper::getAdminName($rec['operator']);
            $rec['dateline'] = date('Y-m-d H:i:s', $rec['dateline']);
        }
        return $data;
    }

    public function getCouponAreaLogListAndTotal($params): array
    {
        $page = (int)array_get($params, 'page', 1);
        $limit = (int)array_get($params, 'limit', 15);

        $filter = [];

        $bigareaId = intval(array_get($params, 'bigarea_id', 0));
        $type = trim(array_get($params, 'type', ''));
        $doc = trim(array_get($params, 'doc', ''));

        $bigareaId && $filter[] = ['bigarea_id', '=', $bigareaId];
        $doc && $filter[] = ['doc', '=', $doc];
        if ($type) {
            $filter[] = ['type', '=', $type];
        } else {
            $filter[] = ['type', 'in', [XsstCouponAreaLog::TYPE_ADD, XsstCouponAreaLog::TYPE_SUB]];
        }

        $data = XsstCouponAreaLog::getListAndTotal($filter, '*', 'id desc', $page, $limit);
        foreach ($data['data'] as &$rec) {
            $rec['operator'] = Helper::getAdminName($rec['operator']);
            $rec['dateline'] = date('Y-m-d H:i:s', $rec['dateline']);
        }
        return $data;
    }

    public function addAmount($params): array
    {
        $id = intval(array_get($params, 'id', 0));
        $doc = trim(array_get($params, 'doc', ''));
        $addAmount = array_get($params, 'add_amount', 0);

        if (empty($doc)) {
            return [false, '请填写OA单号'];
        }
        if (!preg_match('/^[0-9]+$/', $addAmount)) {
            return [false, '增加金额必须是整数数字'];
        }
        if ($addAmount <= 0) {
            return [false, '增加金额不可为负数'];
        }

        $couponAreaManageLog = XsstCouponAreaLog::findOneByWhere([['doc', '=', $doc], ['type', '=', XsstCouponAreaLog::TYPE_ADD]]);
        if (!empty($couponAreaManageLog)) {
            return [false, 'OA单号已使用，无法继续添加'];
        }

        $data = [
            'id' => $id,
            'type' => XsstCouponAreaLog::TYPE_ADD,
            'amount' => $addAmount,
            'doc' => $doc,
            'note' => '',
            'operator' => array_get($params, 'admin_id', 0)
        ];

        list($flg, $rec) = XsstCouponAreaManage::changeCouponAreaBalance($data);

        return [$flg, $flg ? ['after_json' => $data] : $rec];
    }

    public function subAmount($params): array
    {
        $id = array_get($params, 'id', 0);
        $note = array_get($params, 'note', '');
        $subAmount = array_get($params, 'sub_amount', 0);

        if (!$note) {
            return [false, '请填写备注'];
        }

        if (!preg_match('/^[0-9]+$/', $subAmount)) {
            return [false, '扣减金额必须是整数数字'];
        }
        if ($subAmount <= 0) {
            return [false, '扣减金额不可为负数'];
        }

        $data = [
            'id' => $id,
            'type' => XsstCouponAreaLog::TYPE_SUB,
            'amount' => $subAmount,
            'doc' => '',
            'note' => $note,
            'operator' => array_get($params, 'admin_id', 0)
        ];

        list($flg, $rec) = XsstCouponAreaManage::changeCouponAreaBalance($data);

        return [$flg, $flg ? ['after_json' => $data] : $rec];
    }

    public function add($params): array
    {
        $bigarea_id = (int)array_get($params, 'bigarea_id', 0);
        $amount = array_get($params, 'amount', 0);
        $operator = (int)array_get($params, 'admin_id', 0);

        $data = [
            'bigarea_id' => $bigarea_id,
            'amount' => $amount,
            'operator' => $operator,
            'dateline' => time(),
        ];
        list($flg, $rec) = XsstCouponAreaManage::add($data);

        return [$flg, $flg ? ['after_json' => array_merge($data, ['id' => $rec])] : $rec];
    }

    public function delete($params): array
    {
        $id = (int)array_get($params, 'id', 0);
        $couponAreaManage = XsstCouponAreaManage::findOne($id);
        if (empty($couponAreaManage)) {
            return [false, '大区账户管理配置不存在'];
        }

        $flg = XsstCouponAreaManage::deleteById($id);
        return [$flg, $flg ? ['before_json' => $couponAreaManage] : '删除配置错误'];
    }

}