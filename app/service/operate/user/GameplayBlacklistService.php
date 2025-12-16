<?php

namespace Imee\Service\Operate\User;

use Imee\Models\Xs\XsUidGameBlackList;
use Imee\Models\Xs\XsUserBigarea;
use Imee\Models\Xs\XsUserCountry;
use Imee\Models\Xs\XsUserProfile;
use Imee\Models\Xs\XsUserSettings;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;
use Imee\Service\StatusService;
use Imee\Exception\ApiException;
use Imee\Models\Rpc\PsRpc;
use Imee\Models\Xs\XsUidGameBlackListOperationLog;

class GameplayBlacklistService
{
    /**
     * @var PsService $rpcService
     */
    private $rpcService;

    public function __construct()
    {
        $this->rpcService = new PsService();
    }

    public function getList(array $params): array
    {
        $conditions = $this->getConditions($params);
        $list = XsUidGameBlackList::getListAndTotal($conditions, '*', 'id desc', $params['page'] ?? 1, $params['limit'] ?? 15);
        if (empty($list['data'])) {
            return $list;
        }
        $uids = array_column($list['data'], 'uid');
        $userList = XsUserProfile::getBatchCommon($uids, ['uid', 'name']);
        $userSetting = XsUserSettings::getBatchCommon($uids, ['uid', 'language']);
        $userCountry = XsUserCountry::getBatchCommon($uids, ['uid', 'country']);
        $now = time();
        foreach ($list['data'] as &$item) {
            $item['bid'] = $item['id'];
            $item['status'] = $this->setStatus($item['status'], $item['start_time'], $item['end_time'], $now);
            $item['model_id'] = $item['id'];
            $item['country'] = $userCountry[$item['uid']]['country'] ?? '';
            $item['name'] = $userList[$item['uid']]['name'] ?? '';
            $item['language'] = Helper::getLanguageName($userSetting[$item['uid']]['language'] ?? '');
            $item['type'] = XsUidGameBlackList::$blacklistNameMap[$item['type']] ?? '';
            $item['update_time'] = Helper::now($item['update_time']);
            $item['start_time'] = Helper::now($item['start_time']);
            $item['end_time'] = Helper::now($item['end_time']);

            if (in_array($item['source'], [XsUidGameBlackList::SOURCE_USER, XsUidGameBlackList::SOURCE_BROKER])) {
                $user = XsUserProfile::findOne($item['creator']);
                $item['operator'] = [
                    'title' => $item['creator'] . ' - ' . ($user['name'] ?? ''),
                    'value' => $item['creator'] . ' - ' . ($user['name'] ?? ''),
                    'type'  => 'url',
                    'url'   => '/operate/user/user/main?uid=' . $item['creator'],
                ];
            }

            $item['display_type_txt'] = XsUidGameBlackList::$displayType[$item['display_type']] ?? '';
        }
        return $list;
    }

    private function setStatus(int $status, int $startTime, int $endTime, int $now): int
    {
        $state = XsUidGameBlackList::HAVE_STATUS;

        if ($status != XsUidGameBlackList::FOREVER_TIME_TYPE) {
            return $status;
        }
        if ($startTime > $now) {
            $state = XsUidGameBlackList::WAIT_STATUS;
        }
        if ($startTime < $now && $endTime > $now) {
            $state = XsUidGameBlackList::HAVE_STATUS;
        }
        if ($endTime < $now) {
            $state = XsUidGameBlackList::LOSE_STATUS;
        }

        return $state;
    }

    public function checkExists(array $params): array
    {
        $uid = intval($params['uid']);
        $timeType = intval($params['time_type']);
        $types = array_map('intval', $params['type']);
        $startTime = trim($params['start_time'] ?? '');
        $endTime = trim($params['end_time'] ?? '');

        $baseCondition = [['uid', '=', $uid], ['type', 'in', $types]];

        $condition = $baseCondition;
        $condition[] = ['status', '=', XsUidGameBlackList::HAVE_STATUS];
        if ($timeType == XsUidGameBlackList::EFFECT_TIME_TYPE) {
            $stime = $startTime ? strtotime($startTime) : 0;
            $etime = $endTime ? strtotime($endTime) : 0;
        } else {
            $stime = time();
            $etime = strtotime("+ 10 year");
        }

        $condition[] = ['end_time', '>', $stime];
        $condition[] = ['start_time', '<', $etime];

        $recs = XsUidGameBlackList::getListByWhere($condition, 'id,uid,type,start_time,end_time,status');

        $condition = $baseCondition;
        $condition[] = ['status', '=', XsUidGameBlackList::AUDIT_STATUS];
        $recs2 = XsUidGameBlackList::getListByWhere($condition, 'id,uid,type,start_time,end_time,status');
        $recs = array_merge($recs, $recs2);

        $rec = [];
        if ($recs) {
            $user = XsUserProfile::findOne($uid);
            $rec['id'] = implode(',', array_column($recs, 'id'));
            $rec['name'] = $user['name'] ?? '';
            $rec['type'] = implode(',', array_map(function($type) {
                return XsUidGameBlackList::$blacklistNameMap[$type] ?? '';
            }, array_column($recs, 'type')));

            $rec['status'] = implode(',', array_map(function($v) {
                $status = $this->setStatus($v['status'], $v['start_time'], $v['end_time'], time());
                return XsUidGameBlackList::$statusMap[$status] ?? $status;
            }, $recs));
        }

        return $rec;
    }

    public function create(array $params): array
    {
        $data = [
            'uid'       => intval($params['uid']),
            'types'     => array_map('intval', $params['type']),
            'time_type' => intval($params['time_type']),
        ];

        if ($data['time_type'] == XsUidGameBlackList::EFFECT_TIME_TYPE) {
            $data['start_time'] = strtotime($params['start_time']);
            $data['end_time'] = strtotime($params['end_time']);
        }

        return $this->rpcService->createGameBlackList([$data]);
    }

    public function checkBatch(array $params): array
    {
        foreach ($params as $item) {
            if ($item['uid'] == 'UID' || $item['uid'] == 'uid') {
                continue;
            }
            if (!is_numeric($item['uid'])) {
                throw new ApiException(ApiException::MSG_ERROR, '请填写正确的UID');
            }
            if (empty($item['type'])) {
                throw new ApiException(ApiException::MSG_ERROR, '请填写黑名单名称');
            }
            if (!in_array($item['time_type'], [XsUidGameBlackList::EFFECT_TIME_TYPE, XsUidGameBlackList::FOREVER_TIME_TYPE])) {
                throw new ApiException(ApiException::MSG_ERROR, '请填写正确的黑名单时效: 1(永久) 2(期限)');
            }
            $tmp = [
                'uid'       => intval($item['uid']),
                'type'     => array_map('intval', explode(',', $item['type'])),
                'time_type' => intval($item['time_type']),
            ];

            if ($item['time_type'] == XsUidGameBlackList::EFFECT_TIME_TYPE) {
                $tmp['start_time'] = strtotime($item['start_time']);
                $tmp['end_time'] = strtotime($item['end_time']);

                if (!$tmp['start_time'] || !$tmp['end_time']) {
                    throw new ApiException(ApiException::MSG_ERROR, '请填写正确开始结束时间');
                }
            }

            $tmp['time_type'] = $item['time_type'];
            $rec = $this->checkExists($tmp);
            if ($rec) {
                $rec['uid'] = $tmp['uid'];
                return $rec;
            }
        }

        return [];
    }

    public function addBatch(array $params): array
    {
        $map = [];
        foreach ($params as $item) {
            $tmp = [
                'uid'       => intval($item['uid']),
                'types'     => array_map('intval', explode(',', $item['type'])),
                'time_type' => intval($item['time_type']),
            ];

            if ($item['time_type'] == XsUidGameBlackList::EFFECT_TIME_TYPE) {
                $startTime = $item['start_time'] ?? '';
                $endTime = $item['end_time'] ?? '';
                if (empty($startTime) || empty($endTime)) {
                    throw new ApiException(ApiException::MSG_ERROR, '黑名单实效为期限时，请填写正确生效时间、结束时间');
                }
                $tmp['start_time'] = strtotime($startTime);
                $tmp['end_time'] = strtotime($endTime);
            }

            $map[] = $tmp;
        }
        return $this->rpcService->createGameBlackList($map);
    }

    public function deleteBatch(array $params): array
    {
        $ids = $params['id'] ?? [];
        if (empty($ids)) {
            return [false, '删除ID不能为空'];
        }

        $ids = array_map('intval', $ids);

        list($res, $msg) = $this->rpcService->deleteGameBlackList($ids);

        if (!$res) {
            return [false, $msg];
        }

        return [true, ['id' => $ids, 'after_json' => []]];
    }

    public function modify(array $params): array
    {
        $data = [
            'id'        => (int)$params['id'],
            'time_type' => (int)$params['time_type'],
            'operator'  => Helper::getAdminName($params['admin_uid']),
        ];
        $info = XsUidGameBlackList::findOne($data['id']);
        if (empty($info)) {
            return [false, '修改数据不存在'];
        }
        if ($params['time_type'] == XsUidGameBlackList::EFFECT_TIME_TYPE) {
            $data['start_time'] = strtotime($params['start_time']);
            $data['end_time'] = strtotime($params['end_time']);
        }
        list($res, $msg) = $this->rpcService->updateGameBlackList($data);

        if (!$res) {
            return [false, $msg];
        }

        // 日志记录前转时间格式
        $info['update_time'] = Helper::now($info['update_time']);
        $info['start_time'] = Helper::now($info['start_time']);
        $info['end_time'] = Helper::now($info['end_time']);
        $info['dateline'] = Helper::now($info['dateline']);

        if ($data['time_type'] == XsUidGameBlackList::EFFECT_TIME_TYPE) {
            $data['start_time'] = Helper::now($data['start_time']);
            $data['end_time'] = Helper::now($data['end_time']);
        } else {
            $data['start_time'] = Helper::now(time());
            $data['end_time'] = Helper::now(4294967295);
        }

        return [true, ['id' => $data['id'], 'uid' => $info['uid'], 'before_json' => $info, 'after_json' => $data]];
    }

    private function getConditions(array $params): array
    {
        $conditions = [
            //['status', '<>', XsUidGameBlackList::DELETE_STATUS],
        ];

        if (!empty($params['id'])) {
            $conditions[] = ['id', '=', $params['id']];
        }

        if (isset($params['uid']) && !empty($params['uid'])) {
            $conditions[] = ['uid', '=', $params['uid']];
        }
        if (empty($conditions)) {
            $conditions[] = ['uid', '<>', 0];
        }
        if (isset($params['type']) && !empty($params['type'])) {
            $conditions[] = ['type', '=', $params['type']];
        }
        if (isset($params['status']) && !empty($params['status'])) {
            if (in_array($params['status'], [XsUidGameBlackList::CANCEL_STATUS, XsUidGameBlackList::AUDIT_STATUS, XsUidGameBlackList::DELETE_STATUS])) {
                $conditions[] = ['status', '=', $params['status']];
            } else {
                $now = time();
                $conditions[] = ['status', '=', XsUidGameBlackList::HAVE_STATUS];
                if ($params['status'] == XsUidGameBlackList::WAIT_STATUS) {
                    $conditions[] = ['start_time', '>', $now];
                } else if ($params['status'] == XsUidGameBlackList::HAVE_STATUS) {
                    $conditions[] = ['start_time', '<', $now];
                    $conditions[] = ['end_time', '>', $now];
                } else if ($params['status'] == XsUidGameBlackList::LOSE_STATUS) {
                    $conditions[] = ['end_time', '<', $now];
                }
            }
        }

        if (isset($params['bigarea_id']) && !empty($params['bigarea_id'])) {
            $conditions[] = ['bigarea_id', '=', $params['bigarea_id']];
        }

        if (!empty($params['source'])) {
            $conditions[] = ['source', '=', $params['source']];
        }

        return $conditions;
    }

    public function audit(int $id, int $status): array
    {
        // 校验记录是否存在
        $info = XsUidGameBlackList::findOne($id);
        if (!$info) {
            throw new ApiException(ApiException::MSG_ERROR, '记录不存在');
        }
        $operator = Helper::getSystemUserInfo()['user_name'] ?? '';
        $params = [
            'id'       => $id,
            'operator' => $operator,
        ];
        if ($status == XsUidGameBlackList::AUDIT_AGREE) {
            // 审核通过
            list($res, $_) = (new PsRpc())->call(PsRpc::API_APPROVE_GAME_BLACKLIST, ['json' => $params]);
        } elseif ($status == XsUidGameBlackList::AUDIT_REFUSE) {
            // 审核拒绝
            list($res, $_) = (new PsRpc())->call(PsRpc::API_REJECT_GAME_BLACKLIST, ['json' => $params]);
        } else {
            throw new ApiException(ApiException::MSG_ERROR, '审核状态不合法');
        }
        if (!isset($res['common']['err_code']) || $res['common']['err_code'] != 0) {
            $msg = $res['common']['msg'] ?? '操作失败';
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
        return [
            'id'          => $id,
            'before_json' => $info,
            'after_json'  => ['approve_status' => $status],
        ];
    }

    public function getStatusMap()
    {
        return StatusService::formatMap(XsUidGameBlackList::$statusMap, 'label,value');
    }

    public function getTypeMap()
    {
        return StatusService::formatMap(XsUidGameBlackList::$blacklistNameMap, 'label,value');
    }

    public function getAuditStatusMap()
    {
        return StatusService::formatMap(XsUidGameBlackList::$auditStatusMap, 'label,value');
    }

    public function getSourceMap()
    {
        return StatusService::formatMap(XsUidGameBlackList::$sourceMap);
    }

    public function getLogTypeMap()
    {
        return StatusService::formatMap(XsUidGameBlackList::$logType);
    }

    public function getLogList(array $params): array
    {
        if (empty($params['bid'])) {
            throw new ApiException(ApiException::MSG_ERROR, 'bid参数必传');
        }
        $conditions = [
            ['bid', '=', $params['bid']]
        ];
        if (!empty($params['type'])) {
            $conditions[] = ['type', '=', $params['type']];
        }
        if (!empty($params['source'])) {
            $conditions[] = ['source', '=', $params['source']];
        }
        $page = intval($params['page'] ?? 1);
        $limit = intval($params['limit'] ?? 15);
        $result = XsUidGameBlackListOperationLog::getListAndTotal($conditions, '*', 'id desc', $page, $limit);
        $list = $result['data'] ?? [];

        // 批量获取uid对应name
        $uids = array_column($list, 'uid');
        $uidNameMap = [];
        if (!empty($uids)) {
            $uidNameMap = XsUserProfile::getBatchCommon($uids, ['uid', 'name'], 'uid');
        }

        foreach ($list as &$item) {
            // 增加name字段
            $item['name'] = $uidNameMap[$item['uid']]['name'] ?? '';

            // 格式化时间字段
            $item['start_time'] = (!empty($item['start_time']) && $item['start_time'] > 0) ? date('Y-m-d H:i:s', $item['start_time']) : '';
            $item['end_time'] = (!empty($item['end_time']) && $item['end_time'] > 0) ? date('Y-m-d H:i:s', $item['end_time']) : '';
            $item['dateline'] = (!empty($item['dateline']) && $item['dateline'] > 0) ? date('Y-m-d H:i:s', $item['dateline']) : '';

            if (in_array($item['source'], [XsUidGameBlackList::SOURCE_USER, XsUidGameBlackList::SOURCE_BROKER])) {
                $user = XsUserProfile::findOne($item['operator']);
                $item['operator'] = [
                    'title' => $item['operator'] . ' - ' . ($user ? $user['name'] : ''),
                    'value' => $item['operator'] . ' - ' . ($user ? $user['name'] : ''),
                    'type'  => 'url',
                    'url'   => '/operate/user/user/main?uid=' . $item['operator'],
                ];
            }
        }
        unset($item);
        return [
            'data' => $list,
            'total' => $result['total'] ?? 0,
        ];
    }

}