<?php

namespace Imee\Service\Operate\Medal;

use Imee\Models\Xs\XsMedalResource;
use Imee\Models\Xs\XsUserMedal;
use Imee\Models\Xs\XsUserProfile;
use Imee\Models\Xsst\XsstUserMedalLog;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;

class MedalIssuedService
{
    public function getListAndTotal(array $params): array
    {
        $limit = array_get($params, 'limit', 15);
        $page = array_get($params, 'page', 1);

        $id = intval(array_get($params, 'id', 0));
        $uid = intval(array_get($params, 'uid', 0));
        $medalId = intval(array_get($params, 'medal_id', 0));



        $query = [];
        $id && $query[] = ['id', '=', $id];
        $uid && $query[] = ['uid', '=', $uid];
        $medalId && $query[] = ['medal_id', '=', $medalId];

        $data = XsstUserMedalLog::getListAndTotal($query, '*', 'id desc', $page, $limit);


        // 获取用户信息
        $uids = array_column($data['data'], 'uid');
        $uids = array_unique($uids);
        $uids = array_values($uids);
        $userProfile = XsUserProfile::getUserProfileBatch($uids, ['uid', 'name'], 'name');

        // 获取勋章信息
        $medalIds = array_column($data['data'], 'medal_id');
        $medalIds = array_unique($medalIds);
        $medalIds = array_values($medalIds);
        $medal = XsMedalResource::getMedalBatch($medalIds, 'id,description_zh_tw,type');

        foreach ($data['data'] as &$v) {
            $v['uname'] = $userProfile[$v['uid']] ?? '';
            $v['medal_name'] = $medal[$v['medal_id']]['name'] ?? '';
            $v['description'] = $medal[$v['medal_id']]['description'] ?? '';
            $v['medal_type'] = $medal[$v['medal_id']]['type'] ?? 0;

            $v['expire_time'] = $v['dateline'] + $v['expire_time'] * 3600;// 小时转秒
            if (in_array($v['medal_type'], [XsMedalResource::GIFT_MEDAL, XsMedalResource::ACHIEVEMENT_MEDAL])) {
                $v['expire_time'] = '永久有效';
            } else if ($v['expire_time'] < time()) {
                $v['expire_time'] = '已过期';
            } else {
                $v['expire_time'] = ceil(($v['expire_time'] - time()) / 86400);
            }
            $v['dateline'] = $v['dateline'] ? date('Y-m-d H:i:s', $v['dateline']) : '';
        }
        return $data;
    }

    public function add(array $params)
    {
        $uids = str_replace('，', ',', trim($params['uid']));
        $uids = explode(',', $uids);
        $uids = array_filter($uids, function ($uid) {
            return intval($uid) > 0;
        });
        if (empty($uids)) {
            return [false, 'UID输入有误'];
        }
        [$res, $msg] = $this->checkUids($uids);
        if (!$res) {
            return [false, $msg];
        }
        $medal = (int) $params['medal'];
        [$res, $msg] = $this->checkMedal($medal);
        if (!$res) {
            return [false, $msg];
        }
        $source = $params['source'] ?? '';
        // 此处expire_time为天数
        $data = [
            'uid_list' => $uids,
            'medal_id' => $medal,
            'validity_value' => $params['expire_time'] * 86400,
            'remark' => $params['reason'],
            'source_desc' => $source ?:  '官方下发',
        ];
        [$res, $msg, $data] = (new PsService())->userMedalTimeLess($data);
        if (!$res) {
            return [false, $msg];
        }
        $params['success_uid_list'] = $data['success_uid_list'];
        $this->addLog($params);
        if (!empty($data['failure_uid_list'])) {
            return [false, '用户' . implode(',', $data['failure_uid_list']) . '下发失败。'];
        }
        return [true, ''];
    }

    public function addLog(array $params)
    {
        $adminId = $params['admin_id'];
        $adminName = Helper::getAdminName($adminId);
        // 此处expire_time为小时
        $insetBase = [
            'type' => XsstUserMedalLog::SEND_TYPE,
            'medal_id' => $params['medal'],
            'expire_time' => $params['expire_time'] * 24,
            'reason' => $params['reason'],
            'source' => $params['source'] ?? '官方下发',
            'admin_uid' => $adminId,
            'admin_name' => $adminName,
            'dateline' => time()
        ];
        $inset = [];
        foreach($params['success_uid_list'] as $uid) {
            $inset[] = array_merge(['uid' => $uid], $insetBase);
        }
        XsstUserMedalLog::addBatch($inset);
    }

    public function addBatch(array $data, int $adminId)
    {
        $errorLine = [];
        foreach ($data as $key => $item) {
            [$res, $msg] = $this->add(array_merge($item, ['admin_id' => $adminId]));
            if (!$res) {
                $errorLine[] = [
                    'key' => $key + 1,
                    'msg' => $msg
                ];
            } else {
                // 成功时记录下日志
                add_tmp_log($item,  'medalsend.log','json');
            }
            usleep(10000);
        }
        if ($errorLine) {
            $message = '';
            foreach ($errorLine as $error) {
                $message .= "第{$error['key']}行数据发放失败,失败原因：{$error['msg']}" . "\n";
            }
            return [false, $message];
        }
        return [true, ''];
    }

    private function checkMedal(int $id): array
    {
        $medal = XsMedalResource::findOne($id);
        if (empty($medal)) {
            return [false, '当前勋章不存在'];
        }
        if ($medal['type'] != XsMedalResource::HONOR_MEDAL) {
            return [false, '只能下发荣誉勋章'];
        }
        return [true, ''];
    }

    public function checkUids(array $uids): array
    {
        $userList = [];
        $uids = array_unique($uids);
        $uids = array_values($uids);
        //检查uid
        foreach (array_chunk($uids, 50) as $uid) {
            $map = XsUserProfile::getUserProfileBatch($uid, ['uid']);
            $userList = array_merge($userList, $map);
        }
        if (count($uids) != count($userList)) {
            $diffUid = array_diff($uids, array_column($userList, 'uid'));
            return [false, implode(',', $diffUid) . ' UID有误'];
        }
        return [true, ''];
    }

    public function checkMedalIds(array $medalIds): array
    {
        $medalIds = array_unique($medalIds);
        $medalIds = array_values($medalIds);
        $medalList = [];
        foreach (array_chunk($medalIds, 50) as $ids) {
            $map = XsMedalResource::getMedalBatch($ids, 'id, description_zh_tw');
            $medalList = array_merge($medalList, $map);
        }
        if (count($medalIds) != count($medalList)) {
            $diffMedalId = array_diff($medalIds, array_column($medalList, 'id'));
            return [false, implode(',', $diffMedalId) . ' 勋章ID有误'];
        }
        return [true, ''];
    }

    private function getConditions(array $params): array
    {
        $conditions = [];
        if (isset($params['uid']) && !empty($params['uid'])) {
            $conditions[] = ['uid', '=', $params['uid']];
        }
        if (isset($params['medal_id']) && !empty($params['medal_id'])) {
            $conditions[] = ['medal_id', '=', $params['medal_id']];
        }
        return $conditions;
    }
}