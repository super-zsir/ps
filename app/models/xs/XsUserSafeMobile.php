<?php

namespace Imee\Models\Xs;

class XsUserSafeMobile extends BaseModel
{
    public static function findUids($mobile)
    {
        $uids = self::getListByWhere([['mobile', '=', $mobile]], 'uid');
        return array_column($uids, 'uid');
    }


    public static function findAppUids($mobile, $app_id = []): array
    {
        $uids = self::find(array(
            "app_id in ({app_id:array}) and mobile = :mobile:",
            "bind" => (array(
                "mobile" => $mobile,
                'app_id' => $app_id
            ))
        ))->toArray();

        return array_column($uids, 'uid');
    }


    public static function findFirstValue($uid, $columns = '*')
    {
        return static::findFirst(array(
            'conditions' => "uid = :uid:",
            'bind'       => array(
                'uid' => $uid,
            ),
            'columns'    => $columns,
        ));
    }

    public static function findAllUids($appId, $mobile)
    {
        return array_column(self::find(array(
            "app_id = :appId: and mobile = :mobile:",
            "bind" => (array(
                "mobile" => $mobile,
                'appId'  => $appId
            ))
        ))->toArray(), "uid");
    }
}

