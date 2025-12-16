<?php
/**
 * 用户背包
 */

namespace Imee\Models\Xs;

class XsUserCommodity extends BaseModel
{
    protected static $primaryKey = 'id';

    const STATE_MAP = [
        '0'  => '正常',
        '-1' => '过期',
    ];

    const USED_MAP = [
        0 => '否',
        1 => '是',
    ];

    const IN_USE_MAP = [
        0 => '否',
        1 => '是',
    ];

    public static function getInfoByUserUnUsedCid($uid, $cid): array
    {
        $rec = self::useMaster()->findFirst([
            "conditions" => "uid = :uid: and cid = :cid: and used = 0 and state = 0 and in_use=0 and period_end=0",
            "bind"       => ['uid' => $uid, 'cid' => $cid]
        ]);
        if(!$rec){
            return [];
        }
        return $rec->toArray();
    }
}
