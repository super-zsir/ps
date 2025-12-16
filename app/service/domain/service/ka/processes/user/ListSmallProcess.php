<?php

namespace Imee\Service\Domain\Service\Ka\Processes\User;

use Imee\Models\Xs\XsUserProfile;
use Imee\Models\Xsst\Ka\BmsKaUserList;
use Imee\Service\Lesscode\Traits\Curd\ListTrait;
use Imee\Service\Operate\User\UserPriceLevelService;

class ListSmallProcess
{
    use ListTrait;

    /**
     * @var BmsKaUserList
     */
    private $kaUserModel = BmsKaUserList::class;


    public function onRewriteList(): bool
    {
        return true;
    }

    public function onGetFilter(&$filter)
    {
        // todo 组织架构
    }

    public function onList($filter, $params)
    {
        $gid = $params['gid'] ?? 0;

        if ($gid == 0) {
            return ['list' => [], 'total' => 0];
        }

        $smalls = XsUserProfile::getSmallByGid($gid);
        if (empty($smalls)) {
            return ['list' => [], 'total' => 0];
        }

        $uids = array_keys($smalls);

        // 查询uid相关信息
        $userProfile = XsUserProfile::find([
            'columns'    => 'uid,name,dateline,pay_room_money,online_dateline',
            'conditions' => 'uid IN ({uid:array})',
            'bind'       => ['uid' => $uids]
        ])->toArray();

        if (!empty($userProfile)) {
            $userProfile = array_column($userProfile, null, 'uid');
        }

        // 查询ka信息
        $kaUser = $this->kaUserModel::find([
            'columns'    => 'uid,gid,vip,build_al_status,kf_id,build_account',
            'conditions' => 'uid IN ({uid:array})',
            'bind'       => ['uid' => $uids]
        ])->toArray();

        if (!empty($kaUser)) {
            $kaUser = array_column($kaUser, null, 'uid');
        }

        $list = [];
        $priceLevel = UserPriceLevelService::getInstance()->getList($uids);

        foreach ($uids as $uid) {
            if (isset($kaUser[$uid])) {
                $vip = $kaUser[$uid]['vip'];
            } else {
                $vip = $priceLevel[$uid] ?? 0;
            }

            $lastLoginTimeUid = isset($userProfile[$uid]) ? $userProfile[$uid]['online_dateline'] : 0;

            $tmp = [
                'uid'                 => $uid,
                'gid'                 => $smalls[$uid] ?? 0,
                'name'                => isset($userProfile[$uid]) ? $userProfile[$uid]['name'] : '',
                'vip'                 => $vip,
                'wechat'              => isset($kaUser[$uid]) ? $kaUser[$uid]['build_account'] : '',
                'register_time'       => isset($userProfile[$uid]) ? $userProfile[$uid]['dateline'] : '',
                'last_login_time_uid' => $lastLoginTimeUid,
                'build_al_status'     => isset($kaUser[$uid]) ? $kaUser[$uid]['build_al_status'] : '',
                'kf_id'               => isset($kaUser[$uid]) ? $kaUser[$uid]['kf_id'] : 0,
            ];

            $list[] = $tmp;
        }

        return ['list' => $list, 'total' => count($list)];
    }

    public function onListFormat(&$item)
    {
        // TODO: Implement onListFormat() method.
    }

    public function onAfterList($list): array
    {
        return $list;
    }
}
