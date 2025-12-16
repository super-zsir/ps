<?php

namespace Imee\Service\Super;

use Imee\Comp\Operate\Auth\Models\Cms\CmsUser;
use Imee\Models\Rpc\PsRpc;
use Imee\Models\Xs\XsAppSuper;
use Imee\Models\Xs\XsChatroomSuper;
use Imee\Models\Xs\XsUserMobile;
use Imee\Models\Xs\XsUserProfile;
use Imee\Models\Xss\SuperImages;
use Imee\Service\Helper;
use Imee\Service\Lesscode\Traits\SingletonTrait;
use OSS\OssUpload;

class SuperService
{
    use SingletonTrait;
    const LIMIT_NUMBER = 1000000;

    /**
     * 新增图片
     * @param array $data
     * @return void
     */
    public function addImages(array $data)
    {
        $key = md5(uniqid('super'));
        $uid = $data['uid'] ?? 0;
        $rid = $data['rid'] ?? 0;
        $images = $data['images'] ?? [];
        $liveId = $data['last_session_id'] ?? [];
        $time = !empty($data['create_time']) ? $data['create_time'] : time();
        $insert = [];
        if ($images) {
            foreach ($images as $image) {
                $insert[] = [
                    'key' => $key,
                    'image' => $image,
                    'uid' => $uid,
                    'rid' => $rid,
                    'live_id' => $liveId,
                    'dateline' => $time,
                ];
            }
            if ($insert) {
                SuperImages::addBatch($insert);
            }
        }
    }

    /**
     * 当日切图量是否正常
     * @return bool
     */
    public function scanImage()
    {
        $start = strtotime(date('Y-m-d', time()));
        $end = $start + 86400;
        $one = SuperImages::findOneByWhere([
            ['dateline', '>=', $start],
            ['dateline', '<', $end],
        ], 'count(id) as num');
        $number = $one['num'] ?? 0;
        if ($number > self::LIMIT_NUMBER) {
            return false;
        }
        return true;
    }

    /**
     * @param array $data
     * @return array
     */
    public function imageList(array $data): array
    {
        $page = $data['page'] ?? 1;
        $limit = $data['limit'] ?? 15;
        $start = $data['dateline_sdate'] ?? date('Y-m-d', time());
        $end = $data['dateline_edate'] ?? date('Y-m-d', time());
        $start = strtotime($start);
        $end = strtotime($end) + 86400;
        $condition = array(
            ['dateline', '>=', $start],
            ['dateline', '<', $end],
            ['uid', '=', $data['uid'] ?? ''],
            ['rid', '=', $data['rid'] ?? ''],
            ['live_id', '=', $data['live_id'] ?? ''],
        );
        $condition = Helper::filterWhere($condition);
        $data = SuperImages::getListAndTotal($condition, '*', 'id desc', $page, $limit);
        $total = $data['total'];
        $list = $data['data'];
        if ($list) {
            $uids = array_column($list, 'uid');
            $users = XsUserProfile::getListByWhere([
                ['uid', 'in', $uids]
            ]);
            $users = array_column($users, null, 'uid');
            foreach ($list as &$item) {
                $item['dateline'] = date('Y-m-d H:i:s', $item['dateline']);
//                $position = strpos($item['image'], 'agora_new');
//                if ($position !== false) {
                    $item['imgs'] = [Helper::getOssUrl($item['image'], OssUpload::PS_SUPPORT_NEW)];
//                } else {
//                    $item['imgs'] = [Helper::getOssUrl($item['image'])];
//                }
                $item['user_name'] = $users[$item['uid']]['name'] ?? '';
            }
        }
        return ['data' => $list, 'total' => $total];
    }

    public function addAccount(array $data)
    {
        $uid = $data['uid'] ?? 0;
        $adminId = $data['admin_bind'] ?? 0;
        $deleted = $data['deleted'] ?? 0;
        list($res, $code) = (new PsRpc())->call(PsRpc::ADD_SUPER_ADMIN, [
            'json' => [
                'uid' => $uid,
                'admin_id' => $adminId,
                'deleted' => $deleted,
            ]
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [];
        }
        if (isset($res['common']['msg'])) {
            return ['msg' => $res['common']['msg']];
        }
        return ['msg' => '操作失败'];
    }

    public function superAccount(array $data)
    {
        $uids = $data['uids'] ?? [];
        $supers = XsAppSuper::getListByWhere([
            ['uid', 'in', $uids]
        ]);
        $profile = XsUserProfile::getListByWhere([
            ['uid', 'in', $uids]
        ]);
        $profile = array_column($profile, null, 'uid');
        $mobile = XsUserMobile::getListByWhere([
            ['uid', 'in', $uids]
        ]);
        $mobile = array_column($mobile, null, 'uid');
        if ($supers) {
            foreach ($supers as &$value) {
                $value['state'] = $value['deleted'] ? 0 : 1;
                $value['name'] = $profile[$value['uid']]['name'] ?? '';
                $value['head'] = $profile[$value['uid']]['icon'] ?? '';
                $value['mobile'] = $mobile[$value['uid']]['mobile'] ?? '';
            }
        }
        return $supers;
    }

    public function accountList(array $data)
    {
        $uid = $data['uid'] ?? '';
        $admin = $data['admin'] ?? '';
        $data = XsAppSuper::getListAndTotal(Helper::filterWhere([
            ['uid', '=', $uid],
            ['admin_id', '=', $admin],
        ]));
        $total = $data['total'];
        $list = $data['data'];
        foreach ($list as &$datum) {
            $datum['dateline'] = date('Y-m-d H:i:s', $datum['dateline']);
            $datum['admin'] = $datum['admin_id'];
        }
        return ['data' => $list, 'total' => $total];
    }

    public function bind(array $data)
    {
        $uid = $data['uid'] ?? 0;
        $admin = $data['admin'] ?? 0;
        $deleted = $data['deleted'] ?? 0;
        if (!$uid) {
            return ['msg' => '参数异常'];
        }

        return $this->addAccount([
            'uid' => $uid,
            'admin_bind' => $admin,
            'deleted' => $deleted
        ]);
//        if ($admin) {
//            $one = XsAppSuper::findOneByWhere([
//                ['admin_id', '=', $admin],
//                ['deleted', '=', 0]
//            ]);
//            if ($one && $one['uid'] != $uid) {
//                return ['msg' => '当前后台账号已绑定超管账号'];
//            }
//            list($ok, $msg) = XsAppSuper::updateByWhere([
//                ['uid', '=', $uid]
//            ], [
//                'admin_id' => $admin,
//            ]);
//            if (!$ok) {
//                return ['msg' => $msg];
//            }
//        }
//        list($ok, $msg) = XsAppSuper::updateByWhere([
//            ['uid', '=', $uid]
//        ], [
//            'deleted' => $deleted ? 1 : 0
//        ]);
//        if (!$ok) {
//            return ['msg' => $msg];
//        }
//        return [];
    }

    public static function admin()
    {
        $format = [];
        $user = CmsUser::getListByWhere([
            ['user_status', '=', CmsUser::USER_STATUS_VALID]
        ], 'user_id, user_name');
        foreach ($user as $item) {
            $format[] = [
                'label' => $item['user_name'],
                'value' => $item['user_id']
            ];
        }
        return $format;
    }

    public function patrolAccountList(array $data)
    {
        $uid = $data['uid'] ?? '';
        $admin = $data['admin'] ?? '';
        $isOpen = $data['is_open'] ?? '';
        $deleted = $data['deleted'] ?? '';
        $data = XsChatroomSuper::getListAndTotal(Helper::filterWhere([
            ['uid', '=', $uid],
            ['admin_id', '=', $admin],
            ['is_open', '=', $isOpen],
            ['deleted', '=', $deleted],
        ]));
        $total = $data['total'];
        $list = $data['data'];
        foreach ($list as &$datum) {
            $datum['dateline'] = date('Y-m-d H:i:s', $datum['dateline']);
            $datum['admin'] = $datum['admin_id'];
        }
        return ['data' => $list, 'total' => $total];
    }

    public function addPatrolAccount(array $data)
    {
        $uid = $data['uid'] ?? 0;
        $adminId = $data['admin_bind'] ?? 0;
        $deleted = $data['deleted'] ?? 0;
        list($res, $code) = (new PsRpc())->call(PsRpc::ADD_PATROL_ACCOUNT, [
            'json' => [
                'uid' => $uid,
                'admin_id' => $adminId,
                'deleted' => $deleted,
            ]
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [];
        }
        if (isset($res['common']['msg'])) {
            return ['msg' => $res['common']['msg']];
        }
        return ['msg' => '操作失败'];
    }

    public function bindPatrol(array $data)
    {
        $uid = $data['uid'] ?? 0;
        $admin = $data['admin'] ?? 0;
        $deleted = $data['deleted'] ?? 0;
        if (!$uid) {
            return ['msg' => '参数异常'];
        }

        return $this->addPatrolAccount([
            'uid' => $uid,
            'admin_bind' => $admin,
            'deleted' => $deleted
        ]);
    }
}