<?php

namespace Imee\Service\Operate\User;

use Imee\Models\Xs\XsUserSafeMobile;
use Imee\Models\Xsst\XsstUsermobileRecord;
use Imee\Models\Xs\XsUserBindChangeRecords;
use Imee\Models\Xs\XsUserMobile;
use Imee\Models\Xs\XsUserPlatform;
use Imee\Models\Xs\XsUserProfile;
use Imee\Models\Xsst\XsstAdminWhitelist;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;

class UserPlatformService
{
    public function getListAndTotal(array $params): array
    {
        $adminId = intval(array_get($params, 'admin_id', 0));
        $uid = intval(array_get($params, 'uid', 0));
        if (empty($uid)) {
            return ['data' => [], 'total' => 0];
        }

        $userProfile = XsUserProfile::findOne($uid);
        if (empty($userProfile)) {
            return ['data' => [], 'total' => 0];
        }

        $userMobile = XsUserMobile::findOneByWhere([['uid', '=', $uid]]);
        $userSafeMobile = XsUserSafeMobile::findOneByWhere([['uid', '=', $uid]]);

        $data = array(
            'uid'         => $userProfile['uid'],
            'name'        => $userProfile['name'],
            'mobile'      => $userMobile['mobile'] ?? '-',
            'safe_mobile' => $userSafeMobile['mobile'] ?? '-',
        );

        $platStr = array();
        $plats = XsUserPlatform::getListByWhere([['uid', '=', $uid]]);

        $whitelist = XsstAdminWhitelist::findOneByWhere([
            ['type', '=', XsstAdminWhitelist::TYPE_USER_MOBILE],
            ['admin_uid', '=', $adminId],
            ['deleted', '=', XsstAdminWhitelist::DELETE_NO]
        ]);

        foreach ($plats as $v) {
            if ($whitelist) {
                $platStr[] = "[" . $v["platform"] . "]::" . $v["nickname"] . "::" . $v["email"];
            } else {
                $arr = explode('@', $v['email']);
                $email = substr($arr[0], 0, 3) . '**' . substr($arr[0], -3, 3) . '@' . ($arr[1] ?? '');
                $platStr[] = "[" . $v["platform"] . "]::" . $v["nickname"] . "::" . $email;
            }
        }
        $data["plat"] = implode("　", $platStr);


        if (!$whitelist) {
            $data['mobile'] = substr($data['mobile'], 0, 5) . '**' . substr($data['mobile'], -4, 4);
            $data['safe_mobile'] = substr($data['safe_mobile'], 0, 5) . '**' . substr($data['safe_mobile'], -4, 4);
        }

        return ['data' => [$data], 'total' => 1];
    }


    public function getDetailListAndTotal(array $params): array
    {
        $adminId = intval(array_get($params, 'admin_id', 0));
        $limit = array_get($params, 'limit', 15);
        $page = array_get($params, 'page', 1);

        $uid = intval(array_get($params, 'uid', 0));
        if (empty($uid)) {
            return ['data' => [], 'total' => 0];
        }

        $query = [];
        $uid && $query[] = ['uid', '=', $uid];

        $data = XsstUsermobileRecord::getListAndTotal($query, '*', 'id desc', $page, $limit);

        $whitelist = XsstAdminWhitelist::findOneByWhere([
            ['type', '=', XsstAdminWhitelist::TYPE_USER_MOBILE],
            ['admin_uid', '=', $adminId],
            ['deleted', '=', XsstAdminWhitelist::DELETE_NO]
        ]);


        foreach ($data['data'] as &$rec) {
            if (!$whitelist) {
                $rec['omobile'] = substr($rec['omobile'], 0, 5) . '**' . substr($rec['omobile'], -4, 4);
                $rec['nmobile'] = substr($rec['nmobile'], 0, 5) . '**' . substr($rec['nmobile'], -4, 4);
            }
            $rec['admin'] = Helper::getAdminName($rec['admin']);
            $rec['password'] = Helper::decryptData($rec['password']);
            $rec['dateline'] = date('Y-m-d H:i:s', $rec['dateline']);
        }
        return $data;
    }


    public function modifyPhone(array $params)
    {
        $uid = intval(array_get($params, 'uid', 0));
        $useMobile = trim(array_get($params, 'use_mobile', 0));
        $newMobile = trim(array_get($params, 'new_mobile', 0));
        $adminId = intval(array_get($params, 'admin_id', 0));

        if (!Helper::isMobile($newMobile)) {
            return [false, '新手机号格式错误'];
        }

        $newMobileData = XsUserMobile::useMaster()::findOneByWhere([['mobile', '=', $newMobile]]);
        if (!empty($newMobileData)) {
            return [false, '新手机号已被他人所用'];
        }

        $userMobile = XsUserMobile::useMaster()::findOneByWhere([['uid', '=', $uid]]);

        if (empty($userMobile)) {
            $addData = [
                'uid'    => $uid,
                'mobile' => $newMobile,
                'app_id' => APP_ID
            ];
            list($flg, $rec) = XsUserMobile::add($addData);
            $afterJson = $addData;

        } else {
            if ($userMobile['mobile'] != $useMobile) {
                return [false, '手机号校验失败，请检查输入的当前手机号'];
            }
            list($flg, $rec) = XsUserMobile::updateByWhere([['uid', '=', $userMobile['uid']]], ['mobile' => $newMobile]);
            $afterJson = array_merge($userMobile, ['mobile' => $newMobile]);
        }

        // 操作记录
        $flg && XsstUsermobileRecord::add(array(
            'uid'      => $uid,
            'admin'    => $adminId,
            'op'       => 'change',
            'omobile'  => $userMobile['mobile'] ?? '',
            'nmobile'  => $newMobile,
            'dateline' => time()
        ));


        return [$flg, $flg ? ['uid' => $uid, 'before_json' => $userMobile, 'after_json' => $afterJson] : $rec];
    }

    public function modifySafePhone(array $params)
    {
        $uid = intval(array_get($params, 'uid', 0));
        $useMobile = trim(array_get($params, 'use_mobile', 0));
        $newMobile = trim(array_get($params, 'new_mobile', 0));
        $adminId = intval(array_get($params, 'admin_id', 0));

        if (!Helper::isMobile($newMobile)) {
            return [false, '新手机号格式错误'];
        }

        $userProfile = XsUserProfile::findOne($uid);
        if (empty($userProfile)) {
            return [false, '用户不存在'];
        }

        $safeMobile = XsUserSafeMobile::useMaster()::findOneByWhere([['uid', '=', $uid]]);
        $afterJson = [];
        if (empty($safeMobile)) {
            $addData = [
                'uid'      => $uid,
                'mobile'   => $newMobile,
                'app_id'   => APP_ID,
                'dateline' => time()
            ];
            list($flg, $rec) = XsUserSafeMobile::add($addData);
            $flg && XsUserBindChangeRecords::addRecord($uid, 'bind', 'phone');
        } else {
            if ($safeMobile['mobile'] != $useMobile) {
                return [false, '手机号校验失败，请检查输入的当前手机号'];
            }
            list($flg, $rec) = XsUserSafeMobile::updateByWhere([['uid', '=', $uid]], ['mobile' => $newMobile]);
            $flg && XsUserBindChangeRecords::addRecord($uid, 'change', 'phone');
        }
        // 操作记录
        $flg && XsstUsermobileRecord::add(array(
            'uid'      => $uid,
            'admin'    => $adminId,
            'op'       => 'safe_change',
            'omobile'  => $safeMobile['mobile'] ?? '',
            'nmobile'  => $newMobile,
            'dateline' => time()
        ));

        return [$flg, $flg ? ['uid' => $uid, 'before_json' => $safeMobile, 'after_json' => $afterJson] : $rec];
    }

    public function bindPhone(array $params)
    {
        $uid = intval(array_get($params, 'uid', 0));
        $newMobile = trim(array_get($params, 'new_mobile', 0));
        $adminId = intval(array_get($params, 'admin_id', 0));

        if (!Helper::isMobile($newMobile)) {
            return [false, '新手机号格式错误'];
        }

        $userProfile = XsUserProfile::findOne($uid);
        if (empty($userProfile)) {
            return [false, '用户不存在'];
        }

        $newMobileData = XsUserMobile::useMaster()::findOneByWhere([['mobile', '=', $newMobile]]);
        if (!empty($newMobileData)) {
            return [false, '新手机号已被他人所用'];
        }

        $newMobileData = XsUserMobile::useMaster()::findOneByWhere([['uid', '=', $uid]]);
        if (!empty($newMobileData)) {
            return [false, '当前uid已绑定手机号'];
        }

        list($res, $msg, $pwd) = (new PsService)->bindMobile($uid, $newMobile);

        $res && XsstUsermobileRecord::add(array(
            'uid'      => $uid,
            'admin'    => $adminId,
            'op'       => 'bind',
            'password' => Helper::encryptData($pwd),
            'nmobile'  => $newMobile,
            'dateline' => time()
        ));

        return [$res, $res ? ['mobile' => $newMobile, 'pass' => $pwd] : $msg];
    }

}