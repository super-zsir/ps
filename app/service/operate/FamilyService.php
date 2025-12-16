<?php

namespace Imee\Service\Operate;

use Imee\Models\Xs\XsFamily;
use Imee\Models\Xs\XsFamilyMember;
use Imee\Models\Xs\XsUserBigarea;
use Imee\Models\Xs\XsUserProfile;
use Imee\Models\Xsst\XsstFamilyLevelHistory;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;

class FamilyService
{
    public function getListAndTotal(array $params): array
    {
        $conditions = self::getConditions($params);
        $list = XsFamily::getListAndTotal($conditions, '*', 'fid desc', $params['page'] ?? 1, $params['limit'] ?? 15);
        if ($list['total'] == 0) {
            return [];
        }

        $fids = array_column($list['data'], 'fid');
        $numbers = XsFamilyMember::getMemberNumberByFid($fids);
        $uids = array_column($list['data'], 'uid');
        $userInfo = XsUserProfile::getUserProfileBatch($uids);

        foreach ($list['data'] as &$v) {
            $v['badge_url'] = Helper::getHeadUrl($v['badge']);
            $v['create_time'] = Helper::now($v['create_time']);
            $v['member_number'] = $numbers[$v['fid']] ?? '0';
            $v['uname'] = $userInfo[$v['uid']]['name'] ?? '-';
            $v['id'] = $v['fid'];
        }

        return $list;
    }

    public function getMemberListAndTotal(array $params): array
    {
        $conditions = self::getMemberConditions($params);
        $list = XsFamilyMember::getListAndTotal($conditions, '*', 'role asc', $params['page'] ?? 1, $params['limit'] ?? 15);
        if ($list['total'] == 0) {
            return [];
        }

        $uids = array_column($list['data'], 'uid');
        $userInfo = XsUserProfile::getUserProfileBatch($uids);

        foreach ($list['data'] as &$v) {
            $v['role'] = XsFamilyMember::$role[$v['role']] ?? '-';
            $v['join_time'] = Helper::now($v['join_time'] / 1000);
            $v['uname'] = $userInfo[$v['uid']]['name'] ?? '-';
        }

        return $list;
    }

    private function getMemberConditions(array $params): array
    {
        $conditions = [
            ['fid', '=', $params['fid']]
        ];
        if (!empty($params['uid'])) {
            $conditions[] = ['uid', '=', $params['uid']];
        }
        if (!empty($params['start_date'])) {
            $conditions[] = ['join_time', '>=', strtotime($params['start_date'])];
        }
        if (!empty($params['end_date'])) {
            $conditions[] = ['join_time', '<', strtotime($params['end_date'])];
        }
        return $conditions;
    }

    private function getConditions(array $params): array
    {
        $conditions = [];
        if (!empty($params['fid'])) {
            $conditions[] = ['fid', '=', $params['fid']];
        }
        if (!empty($params['uid'])) {
            $conditions[] = ['uid', '=', $params['uid']];
        }
        if (!empty($params['start_date'])) {
            $conditions[] = ['create_time', '>=', strtotime($params['start_date'])];
        }
        if (!empty($params['end_date'])) {
            $conditions[] = ['create_time', '<', strtotime($params['end_date'])];
        }
        if (!empty($params['big_area_id'])) {
            $conditions[] = ['big_area_id', '=', $params['big_area_id']];
        }
        return $conditions;
    }

    public function modify(array $params): array
    {
        $res = XsFamily::findOne($params['fid']);
        if (!$res) {
            return [false, '当前家族不存在'];
        }

        if (empty($params['uid']) || !is_numeric($params['uid']) || $params['uid'] < 1) {
            return [false, '家族长uid不正确'];
        }
        $params['uid'] = trim($params['uid']);
        $uid = $params['uid'];

        if ($uid != $res['uid']) {
            $bigarea = XsUserBigarea::findOne($uid);
            if (!$bigarea || $bigarea['bigarea_id'] != $res['big_area_id']) {
                return [false, '该uid所属大区与家族大区不一致'];
            }

            $rec = XsFamily::findOneByWhere([['fid', '<>', $params['fid']], ['uid', '=', $uid]]);
            if ($rec) {
                return [false, '该uid已归属于其他家族'];
            }

            $rec = XsFamilyMember::findOneByWhere([['fid', '=', $params['fid']], ['uid', '=', $uid]]);
            if (!$rec) {
                return [false, '该uid必须是当前家族成员'];
            }
            $rec = XsUserProfile::findOne($uid);
            if (!$rec || !in_array($rec['deleted'], [XsUserProfile::DELETE_NORMAL, XsUserProfile::DELETE_CANNOT_SEARCH])) {
                return [false, '该uid已被封禁'];
            }

            $params['uid'] = (int)$uid;
        } else {
            unset($params['uid']);
        }

        return (new PsService())->modifyFamily($params);
    }

    public function dismiss(array $params): array
    {
        $res = XsFamily::findOne($params['fid']);
        if (!$res) {
            return [false, '当前家族不存在'];
        }
        return (new PsService())->dismissFamily($params);
    }

    public function removeMember(array $params): array
    {
        $res = XsFamilyMember::findOne($params['id']);
        if (!$res) {
            return [false, $params['id'] . '错误'];
        }
        if ($res['role'] == 1) {
            return [false, '不可删除家族长'];
        }

        return (new PsService())->removeFamilyMember($res);
    }

    public function level(array $params): array
    {
        $fid = $params['fid'] ?? 0;
        $lv = $params['lv'] ?? 0;
        if (empty($fid) || empty($lv)) {
            return [false, 'Params is required'];
        }
        if ($lv < 1 || $lv > 8){
            return [false, '等级区间为1-8'];
        }

        $family = XsFamily::useMaster()::findOne($fid);
        if (empty($family) || $family['lv'] >= $lv) {
            return [false, '家族不存在或者等级低于当前等级'];
        }

        $data = [
            'fid' => (int) $fid,
            'lv'  => (int) $lv,
        ];

        list($res, $msg) = (new PsService())->setFamilyLv($data);
        if (!$res) {
            return [$res, $msg];
        }

        $log = [
            'fid'      => $fid,
            'prev_lv'  => $family['lv'],
            'lv'       => $lv,
            'operator'  => Helper::getAdminName($params['admin_uid']),
            'dateline' => time()
        ];
        XsstFamilyLevelHistory::add($log);
        return [true, 'success'];
    }

    public function levelHistory(array $params, int $page, int $pageSize): array
    {
        $fid = $params['fid'] ?? 0;
        if (empty($fid)) {
            return [];
        }

        $list = XsstFamilyLevelHistory::getListAndTotal([['fid', '=', $fid]], '*', 'id desc', $page, $pageSize);
        if (empty($list['data'])) {
            return $list;
        }

        foreach ($list['data'] as &$item) {
            $item['dateline'] = Helper::now($item['dateline']);
        }

        return $list;
    }
}