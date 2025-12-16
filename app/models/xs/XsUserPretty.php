<?php
namespace Imee\Models\Xs;

use Imee\Comp\Operate\Auth\Models\Cms\CmsModuleUser;
use Imee\Service\Helper;

class XsUserPretty extends BaseModel
{
    public static $primaryKey = 'id';

    const PRETTY_LENGTH = 2;

    const STATUS_VALID = 1;
    const STATUS_INVALID = 2;

    public static $displayStatus = [
        self::STATUS_VALID => '生效中',
        self::STATUS_INVALID => '已失效'
    ];

    const PRETTY_SOURCE_ADMIN = 0;
    const PRETTY_SOURCE_COMMODITY = 1;
    const PRETTY_SOURCE_CUSTOMIZE = 2;

    public static $displayPrettySource = [
        self::PRETTY_SOURCE_ADMIN => '后台下发',
        self::PRETTY_SOURCE_COMMODITY => '商城购买',
        self::PRETTY_SOURCE_CUSTOMIZE => '自选靓号'
    ];

    public static function hasLengthPurview(array $user = []): bool
    {
        if (empty($user)) {
            $user = Helper::getSystemUserInfo();
        }
        if ($user['super'] != 1) {
            $purviews = CmsModuleUser::getUserAllAction($user['user_id']);
            $auth = 'operate/pretty/prettyuser.length';
            if (!in_array($auth, $purviews)) {
                return false;
            }
        }
        return true;
    }

    public function displayStatus()
    {
        if ($this->expire_time > time()) {
            return self::$displayStatus[self::STATUS_VALID];
        }
        return self::$displayStatus[self::STATUS_INVALID];
    }

    public static function getUidByPrettyUid(array $prettyUid): array
    {
        if (empty($prettyUid)) {
            return [];
        }

        $res =  self::getListByWhere([
            ['pretty_uid', 'IN', $prettyUid],
            ['expire_time', '>', time()]
        ], 'uid');
        if (!empty($res)) {
            $res = array_column($res, 'uid');
        }

        return $res;
    }

    /**
     * 获取用户靓号
     * @param array $uidArr
     * @return array
     */
    public static function getListByUidArr(array $uidArr): array
    {
        if (empty($uidArr)) {
            return [];
        }

        $data =  self::getListByWhere([
            ['uid', 'IN', $uidArr],
            ['expire_time', '>', time()]
        ], 'pretty_uid, uid');

        return $data ? array_column($data, 'pretty_uid', 'uid') : [];
    }
}
