<?php

namespace Imee\Models\Xs;

class XsUserMoney extends BaseModel
{
    protected static $primaryKey = 'uid';

    public static function getGameAccount($uids)
    {
        if (empty($uids)) return [];
        $data = self::find([
            'columns' => 'uid,game_coin',
            'conditions' => 'uid IN({ids:array})',
            'bind' => ['ids' => $uids]
        ])->toArray();
        $result = [];
        foreach ($data as $datum) {
            $result[$datum['uid']] = [
                'Char.Coin' => $datum['game_coin'], // 金币
                'Char.Chips' => 0 // 筹码废弃
            ];
        }
        return $result;
    }
    public static function findFirstValue($uid, $master = false)
    {
        $param = [
            "conditions" => "uid=:uid:",
            "bind" => ["uid" => $uid],
        ];

        if ($master) {
            return self::useMaster()->findFirst($param);
        }

        return self::findFirst($param);
    }
    public static function getValue($uid)
    {
        $rec = self::findFirst(array(
            "uid=:uid:",
            "bind" => array("uid" => $uid),
        ));
        if (!$rec) return false;

        return array(
            'money' => intval($rec->money) + intval($rec->money_b),
            'money_cash' => intval($rec->money_cash),
            'money_cash_b' => intval($rec->money_cash_b),
            'money_lock' => intval($rec->money_lock),
            'money_debts' => intval($rec->money_debts),
            'money_order' => intval($rec->money_order) + intval($rec->money_order_b),
            'gold_coin' => intval($rec->gold_coin)
        );
    }

}