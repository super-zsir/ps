<?php

namespace Imee\Models\Xs;

class XsUserIdcard extends BaseModel
{
    public static function idcard($uid)
    {
        $appId = 0;
        $idcard = XsUserIdcard::findFirst(array(
            "uid = :uid: and state > 2",
            "bind" => array("uid" => $uid)
        ));
        if (!$idcard) {
            $safeMobile = XsUserSafeMobile::findOneByWhere([
                ['uid', '=', $uid]
            ]);
            if ($safeMobile) {
                //获取appId的逻辑改到if里面来，可以减少不必要的sql连接次数
                $uprofile = XsUserProfile::findOne($uid);
                if ($uprofile) {
                    $appId = $uprofile['app_id'];
                }
                $mobiles = XsUserSafeMobile::getListByWhere([
                    ['app_id', '=', $appId],
                    ['mobile', '=', $safeMobile['mobile']],
                ]);
                $uids = array_column($mobiles, 'uid');
                if (empty($uids)) {
                    return false;
                }
                $idcard = XsUserIdcard::findFirst(array(
                    "uid in ({ids:array}) and state > 2",
                    "bind" => array("ids" => $uids)
                ));
            }
        }
        return $idcard;
    }
}