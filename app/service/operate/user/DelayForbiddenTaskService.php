<?php

namespace Imee\Service\Operate\User;

use Imee\Models\Xs\XsDelayForbiddenTask;
use Imee\Models\Xs\XsUserProfile;
use Imee\Service\Helper;
use Imee\Service\StatusService;

class DelayForbiddenTaskService
{

    public function getListAndTotal(array $params): array
    {
        $limit = array_get($params, 'limit', 15);
        $page = array_get($params, 'page', 1);

        $id = intval(array_get($params, 'id', 0));
        $uid = intval(array_get($params, 'uid', 0));
        $uidStr = trim(array_get($params, 'uid_str', ''));
        $status = array_get($params, 'status');

        $query = [];
        $id && $query[] = ['id', '=', $id];
        is_numeric($status) && $query[] = ['status', '=', $status];
        if (!empty($uidStr)) {
            $uidArr = Helper::formatUid($uidStr);
            !empty($uidArr) && $query[] = ['uid', 'in', $uidArr];
        } elseif ($uid) {
            $query[] = ['uid', '=', $uid];
        }

        $data = XsDelayForbiddenTask::getListAndTotal($query, '*', 'id desc', $page, $limit);

        if (empty($data['data'])) {
            return $data;
        }
        $allUid = array_unique(array_column($data['data'], 'uid'));
        $uidLists = XsUserProfile::getListByWhere([['uid', 'in', array_values($allUid)]], 'uid,name');
        $uidLists = array_column($uidLists, 'name', 'uid');

        foreach ($data['data'] as &$rec) {
            $_startTime = array_get($rec, 'start_time', 0);
            $_createTime = array_get($rec, 'create_time', 0);
            $rec['start_time'] = $_startTime ? date('Y-m-d H:i:s', $_startTime) : '';
            $rec['create_time'] = $_createTime ? date('Y-m-d H:i:s', $_createTime) : '';

            $rec['name'] = array_get($uidLists, $rec['uid'], '');
            if (!empty($rec['source'])) {
                $rec['source'] = $rec['source'] == 'user_list' ? '用户列表' : '消息举报';
            }
        }
        return $data;
    }

    public static function getStatusMap($value = null, string $format = '')
    {
        $map = XsDelayForbiddenTask::$statusMap;
        if (!empty($value)) {
            return $map[$value] ?? '';
        }
        if (!empty($format)) {
            $map = StatusService::formatMap($map, $format);
        }
        return $map;
    }

}