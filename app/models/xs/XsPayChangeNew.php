<?php

namespace Imee\Models\Xs;

class XsPayChangeNew extends BaseModel
{
    public static $primaryKey = 'id';

    const OP_REASON = array(
        'pay' => '充值',
        'consume' => '消费',
        'income' => '收入',
        'cash' => '提现',
        'change' => '余额提现',
        'income-lock' => '收入锁定',
        'income-unlock' => '收入解锁',
        'income-back' => '退款',
        'back' => '返还',
        'background_give' => '后台下发'
    );

    protected $allowEmptyStringArr = [
        'reason',
        'subject'
    ];

    public static function log($uid, $money, $op, $subject, array $reason = null)
    {
        $rec = self::useMaster();
        $rec->uid = $uid;
        $rec->money = $money;
        $rec->op = $op;
        $rec->dateline = time();
        $rec->subject = $subject;
        $rec->reason = $reason ? serialize($reason) : '';
        $rec->save();
        return $rec;
    }

    public static function logNew($uid, $money, $op, $subject, array $reason = null)
    {
        $rec = self::useMaster();
        $rec->uid = $uid;
        $rec->money = $money;
        $rec->op = $op;
        $rec->dateline = time();
        $rec->subject = $subject;
        $rec->reason = $reason ? json_encode($reason) : '';
        $rec->save();
        return $rec;
    }
}
