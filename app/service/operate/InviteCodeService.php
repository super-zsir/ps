<?php

namespace Imee\Service\Operate;

use Imee\Comp\Operate\Auth\Models\Cms\CmsUser;
use Imee\Models\Xs\XsAgencyHunterInvitationCode;
use Imee\Models\Xs\XsRechargerHunter;
use Imee\Models\Xs\XsUserProfile;
use Imee\Models\Xsst\BmsOperateLog;
use Imee\Service\Rpc\PsService;

class InviteCodeService
{
    public function getListAndTotal($params): array
    {
        $conditions = [];
        if (!empty($params['uid'])) {
            $conditions[] = ['uid', (int)$params['uid']];
        }
        if (!empty($params['code'])) {
            $conditions[] = ['code', $params['code']];
        }
        $result = XsAgencyHunterInvitationCode::getListAndTotal($conditions, '*', 'id desc', $params['page'] ?? 1, $params['limit'] ?? 15);
        if (!$result['data']) {
            return $result;
        }
        $uidList = array_column($result['data'], "uid");
        $where = [["uid", "in", $uidList]];
        $hunterList = XsRechargerHunter::getListByWhere($where);
        $hunterMap = array_column($hunterList, null, "uid");

        $logs = BmsOperateLog::getFirstLogList('invitecode', array_column($result['data'], 'uid'));

        $userInfoList = XsUserProfile::getUserProfileBatch($uidList);

        foreach ($result['data'] as &$v) {
            $userInfo = $userInfoList[$v['uid']] ?? [];
            $v['name'] = $userInfo['name'] ?? '';
            $v['create_time'] = $v['dateline'] ? date('Y-m-d H:i:s', $v['dateline']) : '';
            $v['state'] = isset($hunterMap[$v["uid"]]) && $hunterMap[$v["uid"]]["state"] == 1 ? "生效中" : "失效";
            $v['admin_name'] = $logs[$v['uid']]['operate_name'] ?? '-';
        }
        return $result;
    }

    public function create($uids, $adminId)
    {
        $uids = str_replace("，", ",", $uids);
        $arr = explode(',', $uids);
        if (count($arr) <= 0) {
            return [false, '传值有误'];
        }
        foreach ($arr as $uid) {
            [$result, $msg] = $this->singleCode($uid, $adminId);
            if (!$result) {
                return [false, $msg];
            }
        }
        return ['id' => $arr, 'uid' => 0];
    }

    private function singleCode($uid, $adminId)
    {
        $userInfo = XsUserProfile::findFirstByUid($uid);
        if ($userInfo === false) {
            return ['status' => 1, 'msg' => 'uid不存在，请检查后重试'];
        }
        $inviteInfo = XsAgencyHunterInvitationCode::findFirst([
            'uid=:uid:',
            'bind' => ['uid' => $uid]
        ]);
        if ($inviteInfo) {
            return [false, '该用户已生成邀请码，无需再次生成'];
        }
        $hunterWhere = [
            ["uid", "=", $uid]
        ];
        $hunter = XsRechargerHunter::findOneByWhere($hunterWhere, true);
        if (empty($hunter)) {
            return [false, '当前仅对土豪挖猎人员生成邀请码'];
        }
        $service = new PsService();
        [$result, $msg, $_] = $service->inviteCode($uid);
        if (!$result) {
            return [false, $msg];
        }
        return [true, ''];
    }
}