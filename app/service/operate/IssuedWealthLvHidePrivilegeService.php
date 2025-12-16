<?php

namespace Imee\Service\Operate;

use Imee\Exception\ApiException;
use Imee\Models\Xs\XsUserWealthLvHideLog;
use Imee\Models\Xs\XsUserProfile;
use Imee\Service\Helper;
use Imee\Models\Xsst\XsstIssuedWealthLvHidePrivilege;
use Imee\Comp\Operate\Auth\Models\Cms\CmsUser;
use Imee\Models\Rpc\PsRpc;
use Imee\Service\StatusService;

class IssuedWealthLvHidePrivilegeService
{
    /**
     * 创建下发记录
     */
    public function create($params)
    {
        $uids = preg_split('/[\s,]+/', trim($params['uid']), -1, PREG_SPLIT_NO_EMPTY);
        $uids = array_map('trim', $uids);
        // 1. 检查重复
        if (count($uids) !== count(array_unique($uids))) {
            throw new ApiException(ApiException::MSG_ERROR, 'UID存在重复，请检查后重试');
        }
        // 2. 检查是否为int
        foreach ($uids as $uid) {
            if (!ctype_digit($uid)) {
                throw new ApiException(ApiException::MSG_ERROR, "UID {$uid} 不是有效的，请检查后重试");
            }
        }
        // 3. 检查用户是否存在
        $userProfiles = XsUserProfile::findByIds($uids, 'uid');
        $foundUids = array_column($userProfiles, 'uid');
        $notExist = array_diff($uids, $foundUids);
        if (!empty($notExist)) {
            throw new ApiException(ApiException::MSG_ERROR, '以下UID不存在：' . implode(',', $notExist));
        }
        $days = intval($params['days']);
        $remark = $params['remark'] ?? '';
        $admin_id = $params['admin_id'] ?? 0;
        $now = time();
        // 1. 主表插入
        [$success, $taskId] = XsstIssuedWealthLvHidePrivilege::add([
            'num' => count($uids),
            'days' => $days,
            'remark' => $remark,
            'create_time' => $now,
            'admin_id' => $admin_id,
            'state' => XsstIssuedWealthLvHidePrivilege::STATE_PENDING,
            'send_time' => 0,
        ]);
        if (!$success) {
            throw new ApiException(ApiException::MSG_ERROR, '任务创建失败: ' . $taskId);
        }
        // 2. 调用RPC下发
        $rpc = new PsRpc();
        $rpcData = [
            'task_id' => (int)$taskId,
            'days' => $days,
            'uids' => array_map('intval', $uids),
            'remark' => $remark,
            'operator' => Helper::getAdminName($admin_id),
        ];

        [$resp, $code] = $rpc->call(PsRpc::API_SEND_WEALTH_LV_HIDE_PRIVILEGE, ['json' => $rpcData]);
        $isSuccess = $code == 200
            && is_array($resp)
            && isset($resp['common']['err_code'])
            && $resp['common']['err_code'] == 0;
        if (!$isSuccess) {
            $errMsg = $resp['common']['msg'] ?? $resp['error'] ?? 'RPC下发失败';
            XsstIssuedWealthLvHidePrivilege::edit($taskId, [
                'state' => XsstIssuedWealthLvHidePrivilege::STATE_FAIL
            ]);
            throw new ApiException(ApiException::MSG_ERROR, $errMsg);
        }
        XsstIssuedWealthLvHidePrivilege::edit($taskId, [
            'state' => XsstIssuedWealthLvHidePrivilege::STATE_SUCCESS,
            'send_time' => time(),
        ]);
        return ['id' => $taskId, 'after_json' => $params];
    }

    /**
     * 校验下发逻辑，防止重复发放等
     */
    public function checkCreate($params)
    {
        $uids = preg_split('/[\s,]+/', trim($params['uid'] ?? ''), -1, PREG_SPLIT_NO_EMPTY);
        $days = intval($params['days'] ?? 0);
        if ($days < 1) {
            return ['is_info' => true, 'msg' => '发放天数必须大于0'];
        }
        if (count($uids) > 500) {
            return ['is_info' => true, 'msg' => '单次最多下发500个用户'];
        }
        $uidMap = array_count_values($uids);
        foreach ($uidMap as $k => $v) {
            if (!is_numeric($k)) {
                return ['is_info' => true, 'msg' => "存在非整型UID：{$k}，请检查后重试。"];
            }
            if ($v > 1) {
                return ['is_info' => true, 'msg' => "存在重复UID：{$k}，请检查后重试。"];
            }
        }
        // 检查15天内是否有发放记录
        $startTime = strtotime(date('Y-m-d')) - 15 * 86400;
        $logs = XsUserWealthLvHideLog::getListByWhere([
            ['create_time', '>=', $startTime],
            ['uid', 'in', $uids]
        ], 'uid,days,remark,create_time,operator', 'uid asc');
        if ($logs) {
            $userMap = XsUserProfile::getUserProfileBatch($uids);
            $adminName = Helper::getAdminName($params['admin_id'] ?? 0);
            $filePath = '/tmp/wealth_lv_hide_privilege_log_' . uniqid() . '.csv';
            @file_put_contents($filePath, "uid,昵称,权益天数,下发时间,发放数量,操作人" . PHP_EOL);
            $rows = [];
            foreach ($logs as $v) {
                $rows[] = sprintf('%s,%s,%s,%s,%s,%s' . PHP_EOL,
                    $v['uid'],
                    $userMap[$v['uid']]['name'] ?? '',
                    $v['days'],
                    date('Y-m-d H:i:s', $v['create_time']),
                    1,
                    $v['operator'] ?? ''
                );
            }
            @file_put_contents($filePath, $rows, FILE_APPEND);
            $url = Helper::uploadOss($filePath);
            if (ENV == 'dev') {
                $url = explode('?', $url);
                $url = str_replace('http:', 'https:', $url[0]);
            }

            @unlink($filePath);
            $table = [];
            $table[] = '<table border="1" style="border-collapse: collapse">';
            $table[] = '<tr><td>uid</td><td>昵称</td><td>权益天数</td><td>下发时间</td><td>发放数量</td><td>操作人</td></tr>';
            foreach ($logs as $v) {
                $table[] = sprintf('<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>1</td><td>%s</td></tr>',
                    $v['uid'],
                    $userMap[$v['uid']]['name'] ?? '',
                    $v['days'],
                    date('Y-m-d H:i:s', $v['create_time']),
                    $v['operator'] ?? ''
                );
            }
            $table[] = '</table>';
            $msg = '以下用户15日内有发放记录，确认发放吗？<a href="' . $url . '" target="_blank" rel="noopener noreferrer">下载明细</a><br/>' . implode('', $table);
            return ['is_confirm' => true, 'msg' => $msg];
        }
        return ['is_confirm' => false];
    }

    /**
     * 下载15天内发放明细csv，返回OSS链接
     */
    public function downloadDetail($params)
    {
        $uids = preg_split('/[\s,]+/', trim($params['uid'] ?? ''), -1, PREG_SPLIT_NO_EMPTY);
        $startTime = strtotime(date('Y-m-d')) - 15 * 86400;
        $logs = XsUserWealthLvHideLog::getListByWhere([
            ['create_time', '>=', $startTime],
            ['uid', 'in', $uids]
        ], 'uid,days,remark,create_time,operator', 'uid asc');
        // 获取昵称和操作人
        $userMap = [];
        if ($uids) {
            $userMap = XsUserProfile::getUserProfileBatch($uids);
        }
        $adminName = Helper::getAdminName($params['admin_id'] ?? 0);
        $filePath = '/tmp/wealth_lv_hide_privilege_log_' . uniqid() . '.csv';
        @file_put_contents($filePath, "uid,昵称,发放天数,下发时间,备注,操作人\n");
        $rows = [];
        foreach ($logs as $v) {
            $rows[] = sprintf('%s,%s,%s,%s,%s,%s\n',
                $v['uid'],
                $userMap[$v['uid']]['name'] ?? '',
                $v['days'],
                date('Y-m-d H:i:s', $v['create_time']),
                str_replace(["\n", "\r", ","], ' ', $v['remark']),
                $v['operator'] ?? ''
            );
        }
        @file_put_contents($filePath, $rows, FILE_APPEND);
        $url = Helper::uploadOss($filePath);
        @unlink($filePath);
        return $url;
    }

    /**
     * 获取任务列表，支持uid搜索
     */
    public function getList($params)
    {
        $cond = [];
        // uid搜索，先查明细表再查主表
        if (!empty($params['id']) && !is_numeric($params['id'])) {
            return [];
        }
        if (!empty($params['uid']) && !is_numeric($params['uid'])) {
            return [];
        }
        if (!empty($params['uid'])) {
            $taskIds = XsUserWealthLvHideLog::getListByWhere([
                ['uid', '=', $params['uid']]
            ], 'task_id');
            $taskIds = array_column($taskIds, 'task_id');
            if (!$taskIds) {
                return [];
            }

            if (!empty($params['id']) && is_numeric($params['id'])) {
                if (in_array($params['id'], $taskIds)) {
                    $cond[] = ['id', '=', $params['id']];
                } else {
                    return [];
                }
            } else {
                $cond[] = ['id', 'in', $taskIds];
            }
        } else {
            if (!empty($params['id']) && is_numeric($params['id'])) {
                $cond[] = ['id', '=', $params['id']];
            }
        }
        if (!empty($params['state'])) {
            $cond[] = ['state', '=', $params['state']];
        }
        if (!empty($params['admin_id'])) {
            $cond[] = ['admin_id', '=', $params['admin_id']];
        }
        // 按照 dateline_sdate, dateline_edate 搜索
        if (!empty($params['dateline_sdate'])) {
            $cond[] = ['create_time', '>=', strtotime($params['dateline_sdate'])];
        }
        if (!empty($params['dateline_edate'])) {
            $cond[] = ['create_time', '<', strtotime($params['dateline_edate'])];
        }
        $result = XsstIssuedWealthLvHidePrivilege::getListAndTotal($cond, '*', 'id desc', $params['page'] ?? 1, $params['limit'] ?? 20);
        // 格式化时间、查操作人
        $adminIds = array_column($result['data'], 'admin_id');
        $adminMap = [];
        if ($adminIds) {
            $adminMap = CmsUser::getAdminUserBatch($adminIds);
        }
        foreach ($result['data'] as &$row) {
            $row['task_id'] = $row['id'];
            $row['create_time'] = $row['create_time'] ? date('Y-m-d H:i:s', $row['create_time']) : '';
            $row['send_time'] = $row['send_time'] ? date('Y-m-d H:i:s', $row['send_time']) : '';
            $row['admin_name'] = $adminMap[$row['admin_id']]['user_name'] ?? '';
            $row['display_state'] = XsstIssuedWealthLvHidePrivilege::$stateMap[$row['state']] ?? '';
        }
        return $result;
    }

    /**
     * 获取发放明细列表
     */
    public function getLogList($params)
    {
        $cond = [];
        if (empty($params['task_id'])) {
            return [];
        }
        $cond[] = ['task_id', '=', $params['task_id']];
        if (!empty($params['uid'])) {
            $cond[] = ['uid', '=', $params['uid']];
        }
        if (!empty($params['dateline_sdate'])) {
            $cond[] = ['create_time', '>=', strtotime($params['dateline_sdate'])];
        }
        if (!empty($params['dateline_edate'])) {
            $cond[] = ['create_time', '<', strtotime($params['dateline_edate']) + 86400];
        }
        $list = XsUserWealthLvHideLog::getListAndTotal($cond, '*', 'id desc', $params['page'] ?? 1, $params['limit'] ?? 20);
        // 查找任务表和操作人
        $taskIds = array_column($list['data'], 'task_id');
        $tasks = $taskIds ? XsstIssuedWealthLvHidePrivilege::getListByWhere([
            ['id', 'in', $taskIds]
        ], 'id,admin_id') : [];
        $adminIds = array_column($tasks, 'admin_id');
        $adminMap = $adminIds ? CmsUser::getAdminUserBatch($adminIds) : [];
        $taskMap = [];
        foreach ($tasks as $t) {
            $taskMap[$t['id']] = $t;
        }
        foreach ($list['data'] as &$row) {
            $row['create_time'] = Helper::now($row['create_time']);
            $row['admin_name'] = isset($taskMap[$row['task_id']]) && isset($adminMap[$taskMap[$row['task_id']]['admin_id']]) ? $adminMap[$taskMap[$row['task_id']]['admin_id']] : '';
        }
        unset($row);
        return $list;
    }

    /**
     * 导出发放明细列表
     */
    public function exportLogList($params)
    {
        $list = $this->getLogList($params);
        $rows = $list['data'];
        $header = ['id','task_id','uid','days','admin_name'];
        $csv = implode(',', $header) . "\n";
        foreach ($rows as $row) {
            $csv .= sprintf("%s,%s,%s,%s,%s\n", $row['id'], $row['task_id'], $row['uid'], $row['days'], $row['admin_name']);
        }
        $file = '/tmp/issued_wealth_lv_hide_privilege_log_' . uniqid() . '.csv';
        file_put_contents($file, $csv);
        // 导出本地csv文件，返回本地路径（如需上线请替换为OSS上传）
        $url = '/tmp/' . basename($file);
        return $url;
    }

    public function getStateMap()
    {
        return StatusService::formatMap(XsstIssuedWealthLvHidePrivilege::$stateMap);
    }

    public function getAdminMap()
    {
        $data = CmsUser::getAdminUserMap([['system_id', '=', CMS_USER_SYSTEM_ID], ['user_status', '=', CmsUser::USER_STATUS_VALID]]);
        return StatusService::formatMap($data);
    }

    public function getDaysMap()
    {
        $map = [
            3 => '3',
            7 => '7',
            15 => 15,
            30 => 30,
        ];

        return StatusService::formatMap($map);
    }
}
