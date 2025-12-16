<?php

namespace Imee\Service\Operate\Certification;

use Imee\Exception\ApiException;
use Imee\Models\Xs\XsCertificationSign;
use Imee\Models\Xs\XsUserCertificationSign;
use Imee\Models\Xs\XsUserProfile;
use Imee\Models\Xsst\XsstCertificationLog;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;

class CertificationSendService
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

        $list = XsstCertificationLog::getListAndTotal($conditions, '*', 'id desc', $params['page'], $params['limit']);

        if (empty($list['data'])) {
            return $list;
        }
        $uids = array_column($list['data'], 'uid');
        $userList = XsUserProfile::getUserProfileBatch($uids);
        foreach ($list['data'] as &$item) {
            if ($item['valid_day'] == -1) {
                $item['valid_day'] = '永久有效';
            }
            $item['nickname'] = $userList[$item['uid']]['name'] ?? '';
            $item['review_time'] = $item['review_time'] ? Helper::now($item['review_time']) : '';
            $item['dateline'] = $item['dateline'] ? Helper::now($item['dateline']) : '';
        }

        return $list;
    }

    public function send(array $params): array
    {
        $uidList = $this->formatUid($params['uid']);
        $absent = $this->checkUid($uidList);
        if (!empty($absent)) {
            return [false, '以下用户UID不存在：' . implode(',', $absent)];
        }
        $adminId = $params['admin_id'];
        $logs = [];
        $materials = XsCertificationSign::findOne($params['cer_id']);
        $baseLog = [
            'cer_id'    => $params['cer_id'],
            'cer_name'  => $materials['name'] ?? '',
            'content'   => $params['content'] ?? '',
            'remark'    => $params['remark'] ?? '',
            'valid_day' => $params['valid_day'],
            'creator'   => Helper::getAdminName($adminId),
            'state'     => XsstCertificationLog::AUDIT_STATE_DEFAULT,
            'tid_index' => $params['tid_index'] ?? '',
            'dateline'  => time(),
        ];
        foreach ($uidList as $uid) {
            $logs[] = array_merge($baseLog, ['uid' => $uid]);
        }
        list($r, $m, $_) = XsstCertificationLog::addBatch($logs);
        if (!$r) {
            return [false, $m];
        }
        return [true, ''];
    }

    public function sendBatch(array $params): array
    {
        $params['list'] = json_decode($params['list'], true);
        $adminId = $params['admin_id'];
        $logs = [];
        $baseLog = [
            'creator' => Helper::getAdminName($adminId),
            'state' => XsstCertificationLog::AUDIT_STATE_DEFAULT,
            'dateline' => time(),
        ];
        $cerIds = array_column($params['list'], 'cer_id');
        $cerIds = array_values(array_unique($cerIds));
        $certificationSigns = XsCertificationSign::getBatchCommon($cerIds, ['id', 'name'], 'id');
        $uids = array_column($params['list'], 'uid');
        $absent = $this->checkUid($uids);
        if (!empty($absent)) {
            return [false, '以下用户UID不存在：' . implode(',', $absent)];
        }
        foreach ($params['list'] as $item) {
            $logs[] = array_merge($baseLog, [
                'uid' => $item['uid'],
                'cer_name' => $certificationSigns[$item['cer_id']]['name'] ?? '',
                'cer_id' => $item['cer_id'],
                'content' => $item['content'] ?? '',
                'remark' => $item['remark'] ?? '',
                'valid_day' => $item['valid_day']
            ]);
        }

        list($r, $m, $_) = XsstCertificationLog::addBatch($logs);
        if (!$r) {
            return [false, $m];
        }
        return [true, ''];
    }

    public function checkUid(array $uids)
    {
        $absent = [];
        foreach (array_chunk($uids, 200) as $item) {
            $list = XsUserProfile::getListByWhere([
                ['uid', 'in', $item]
            ], 'uid');
            if (empty($list)) {
                $absent = array_merge($absent, $item);
                continue;
            }
            $uids = array_column($list,'uid');
            $diff = array_diff($item, $uids);
            if ($diff) {
                $absent = array_merge($absent, $diff);
            }
        }
        return $absent;
    }

    public function beforeSendBatch(array $params)
    {
        $exists = [];
        $existsStr = '';
        foreach ($params['data'] as $list) {
            $exists = array_merge($this->check([$list['uid']], intval($list['cer_id'])), $exists);
        }
        if ($exists) {
            $existsStr = implode(',', $exists);
        }
        return ['list' => json_encode($params['data'], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE), 'exists' => $existsStr];
    }

    public function beforeSend(array $params)
    {
        $uidList = $this->formatUid($params['uid']);

        $exists = $this->check($uidList, $params['cer_id'] ?? 0);

        if ($exists) {
            return implode(',', $exists);
        }

        return '';
    }

    public function check(array $uidList, int $cerId): array
    {
        if (empty($uidList) || $cerId < 1) {
            return [];
        }
        $existsIds = [];
        $list = XsUserCertificationSign::getListByWhere([
            ['uid', 'in', $uidList],
            ['cer_id', '=', $cerId],
            ['expire_dateline', '>', time()]
        ]);
        if ($list) {
            $existsIds = array_column($list, 'uid');
        }
        return $existsIds;
    }

    public function getConditions(array $params): array
    {
        $conditions = [];

        if (isset($params['cer_id']) && !empty($params['cer_id'])) {
            $conditions[] = ['cer_id', '=', $params['cer_id']];
        }

        if (isset($params['cer_name']) && !empty($params['cer_name'])) {
            $conditions[] = ['cer_name', 'like', "%{$params['cer_name']}%"];
        }

        if (isset($params['uid']) && !empty($params['uid'])) {
            $conditions[] = ['uid', '=', $params['uid']];
        }

        if (isset($params['state'])) {
            $conditions[] = ['state', '=', $params['state']];
        }

        if (isset($params['creator'])) {
            $conditions[] = ['creator', 'like', $params['creator']];
        }

        if (isset($params['review_time_sdate']) && !empty($params['review_time_sdate'])
            && isset($params['review_time_edate']) && !empty($params['review_time_edate'])) {
            $conditions[] = ['review_time', '>=', strtotime($params['review_time_sdate'])];
            $conditions[] = ['review_time', '<=', strtotime($params['review_time_edate']) + 86400];
        }

        if (isset($params['dateline_sdate']) && !empty($params['dateline_sdate'])
            && isset($params['dateline_edate']) && !empty($params['dateline_edate'])) {
            $conditions[] = ['dateline', '>=', strtotime($params['dateline_sdate'])];
            $conditions[] = ['dateline', '<', strtotime($params['dateline_edate']) + 86400];
        }

        return $conditions;
    }

    private function formatUid($uid)
    {
        $uid = str_replace('，', ',', $uid);
        $uid = explode(',', $uid);
        $uid = array_map('intval', $uid);
        $uid = array_filter($uid);
        $uid = array_unique($uid);
        $uid = array_values($uid);

        return $uid;
    }

    public function audit(array $params): array
    {
        $ids = $this->formatUid($params['id']);
        $defaultStateIds = XsstCertificationLog::getAuditListByIds($ids, XsstCertificationLog::AUDIT_STATE_DEFAULT, 'id');
        if (empty($defaultStateIds)) {
            return [true, ''];
        }
        $unauditedIds = array_column($defaultStateIds, 'id');
        $unauditedIds = array_intersect($ids, $unauditedIds);
        $adminId = $params['admin_id'];
        $adminName = Helper::getAdminName($adminId);
        $time = time();
        $updateLog = [];
        foreach ($unauditedIds as $id) {
            $updateLog[$id] = [
                'state' => $params['state'],
                'operator' => $adminName,
                'review_time' => $time
            ];
        }

        list($res, $msg, $rows) = XsstCertificationLog::updateBatch($updateLog);
        if (!$res || $rows != count($unauditedIds)) {
            return [false, '更新状态失败，失败原因：' . $msg];
        }

        if ($params['state'] == XsstCertificationLog::AUDIT_STATE_SUCCESS) {
            $logs = XsstCertificationLog::useMaster()::getAuditListByIds(array_values($unauditedIds), XsstCertificationLog::AUDIT_STATE_SUCCESS, 'id, cer_id, content, uid, valid_day, remark');
            $message = $data = [];
            foreach ($logs as $log) {
                $validTime = (int)($log['valid_day'] < 0 ? -1 : ($log['valid_day'] * 86400));
                $key = $log['cer_id'] . $log['content'] . $log['remark'] . $validTime;
                if (isset($data[$key])) {
                    $data[$key]['uid_list'][] = $log['uid'];
                } else {
                    $data[$key] = [
                        'uid_list' => [(int)$log['uid']],
                        'cer_id' => (int)$log['cer_id'],
                        'content' => $log['content'],
                        'remark' => $log['remark'],
                        'validity_value' => $validTime
                    ];
                }
            }
            foreach ($data as $item) {
                list($res, $msg) = $this->rpcService->giveCertificationSign($item);
                if (!$res) {
                    $message[] = "用户UID{$log['uid']}发放认证素材{$log['cer_id']}失败。失败原因：{$msg}";
                }
                usleep(1000);
            }
            if ($message) {
                return [false, implode(',', $message)];
            }
        }
        return [true, ''];
    }

    public function getCerId()
    {
        $list = XsCertificationSign::getListByWhere([], 'id, name, default_content');
        $map = [];
        foreach ($list as $item) {
            $map[] = [
                'label' => $item['id'] . '-' . $item['name'],
                'value' => $item['id'] . '-' . $item['default_content']
            ];
        }

        return $map;
    }

    public function sendAndAudit($params): void
    {
        list($sendRes, $sendMsg) = $this->send($params);
        if (!$sendRes) {
            throw new ApiException(ApiException::MSG_ERROR, $sendMsg);
        }

        usleep(1000 * 10);
        $recordList = XsstCertificationLog::getListByTidIndex($params['tid_index']);
        if ($recordList) {
            $data = [
                'id'       => implode(',', $recordList),
                'state'    => XsstCertificationLog::AUDIT_STATE_SUCCESS,
                'admin_id' => $params['admin_id']
            ];
            list($auditRes, $auditMsg) = $this->audit($data);
            if (!$auditRes) {
                throw new ApiException(ApiException::MSG_ERROR, $auditMsg);
            }
        }
    }
}