<?php
/**
 * 登陆验证码管理
 */

namespace Imee\Service\Operate;

use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xs\XsUserBigarea;
use Imee\Models\Xs\XsUserMobile;
use Imee\Models\Xsst\BmsVerifyCode;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;

class VerifyCodeService
{
    public function getListAndTotal($params): array
    {
        $conditions = [];
        if (!empty($params['uid'])) {
            $conditions[] = ['uid', (int)$params['uid']];
        }
        if (!empty($params['create_time_start'])) {
            $conditions[] = ['create_time', '>=', strtotime($params['create_time_start'])];
        }
        if (!empty($params['create_time_end'])) {
            $conditions[] = ['create_time', '<=', strtotime($params['create_time_end'])];
        }

        $column = "id,uid,create_time,code,act_uid,act_name,area";
        $result = BmsVerifyCode::getListAndTotal($conditions, $column, 'id desc', $params['page'] ?? 1, $params['limit'] ?? 15);
        if (!$result['data']) {
            return $result;
        }

        foreach ($result['data'] as &$v) {
            $v['create_time'] = $v['create_time'] ? date('Y-m-d H:i:s', $v['create_time']) : '';
            $v['area'] = XsBigarea::getBigAreaCnName($v['area']);
        }
        return $result;
    }

    public function create($uid, $adminId)
    {
        $userInfo = XsUserMobile::findFirstByUid($uid);
        if (!$userInfo) {
            return [false, '用户不存在'];
        }
        if (!$userInfo->mobile) {
            return [false, '手机号不存在'];
        }
        $service = new PsService();
        [$result, $msg, $rs] = $service->loginSmsCode($uid);
        if (!$result) {
            return [false, $msg];
        }

        $bmsVerifyCode = new BmsVerifyCode();
        $bmsVerifyCode->uid = $uid;
        $bmsVerifyCode->create_time = time();
        $bmsVerifyCode->code = $rs['sms_code'];
        $bmsVerifyCode->act_uid = $adminId;
        $bmsVerifyCode->phone = $userInfo->mobile;
        $bmsVerifyCode->act_name = Helper::getSystemUserName();
        $bmsVerifyCode->area = XsUserBigarea::userBigArea($uid);
        $bmsVerifyCode->save();
        return [true, ''];
    }
}