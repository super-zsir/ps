<?php

namespace Imee\Service\Operate\Medal;

use Imee\Models\Xs\XsMedalResource;
use Imee\Models\Xs\XsUserMedal;
use Imee\Models\Xs\XsUserProfile;
use Imee\Models\Xsst\XsstUserMedalLog;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;

class UserMedalService
{
    /**
     * @var PsService
     */
    private $rpcService;

    public function __construct()
    {
        $this->rpcService = new PsService();
    }

    public function getList(array $params, int $page, int $pageSize): array
    {
        $conditions = $this->getConditions($params);
        $res = XsUserMedal::getListAndTotal($conditions, '*', 'id desc', $page, $pageSize);
        if ($res['total'] == 0) {
            return [];
        }
        // 获取用户信息
        $uids = array_column($res['data'], 'uid');
        $uids = array_unique($uids);
        $uids = array_values($uids);
        $userProfile = XsUserProfile::getUserProfileBatch($uids, ['uid', 'name'], 'name');

        // 获取勋章信息
        $medalIds = array_column($res['data'], 'medal_id');
        $medalIds = array_unique($medalIds);
        $medalIds = array_values($medalIds);
        $medal = XsMedalResource::getMedalBatch($medalIds, 'id,description_zh_tw,type');
        foreach ($res['data'] as &$v) {
            $v['uname'] = $userProfile[$v['uid']] ?? '';
            $v['medal_name'] = $medal[$v['medal_id']]['name'] ?? '';
            $v['description'] = $medal[$v['medal_id']]['description'] ?? '';
            $v['medal_type'] = $medal[$v['medal_id']]['type'] ?? 0;
            if (in_array($v['medal_type'], [1, 3])) {
                $v['expire_time'] = '永久有效';
                $v['status'] = 1;
            } else if ($v['medal_type'] == 2) {
                if ($v['expire_time'] < time()) {
                    $v['status'] = 2;
                } else {
                    $v['status'] = 1;
                }
                $v['expire_time'] = Helper::now($v['expire_time']);
            } else {
                $v['expire_time'] = '';
                $v['status'] = 0;
            }
        }
        return $res;
    }

    public function lessTime(array $params): array
    {
        $userMedal = XsUserMedal::findOne($params['id']);

        if (empty($userMedal)) {
            return [false, '当前用户勋章不存在'];
        }

        $medal = XsMedalResource::findOne($params['medal_id']);
        if (empty($medal)) {
            return [false, '勋章不存在'];
        }

        if ($medal['type'] == XsMedalResource::GIFT_MEDAL) {
            return [false, '礼物勋章有效期为永久，不支持进行扣除'];
        }

        $data = [
            'uid_list' => explode(',', $params['uid']),
            'medal_id' => $params['medal_id'],
            'validity_value' => $params['expire_time'] * -3600,
            'remark' => $params['reason'] ?? '',
        ];
        [$res, $msg] = $this->rpcService->userMedalTimeLess($data);
        if (!$res) {
            return [false, $msg];
        }
        $adminId = Helper::getSystemUid();
        $adminName = Helper::getAdminName($adminId);
        XsstUserMedalLog::add([
            'sid' => $params['id'],
            'uid' => $userMedal['uid'],
            'medal_id' => $userMedal['medal_id'],
            'expire_time' => $params['expire_time'],
            'reason' => $params['reason'] ?? '',
            'admin_uid' => $adminId,
            'admin_name' => $adminName,
            'dateline' => time(),
            'type' => XsstUserMedalLog::LESS_TIME_TYPE
        ]);
        return [true, ''];
    }

    public function getUserMedalLogList(array $params, int $page, int $pageSize): array
    {
        $list = XsstUserMedalLog::getListAndTotal([
            ['uid', '=', $params['uid']],
            ['medal_id', '=', $params['medal_id']],
            ['type', '=', XsstUserMedalLog::LESS_TIME_TYPE]
        ], '*', 'id desc', $page ,$pageSize);
        if ($list['total'] == 0) {
            return [];
        }
        foreach ($list['data'] as &$v) {
            $v['dateline'] = Helper::now($v['dateline']);
        }
        return $list;
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
        if (isset($params['status']) && !empty($params['status'])) {
            if ($params['status'] == 2) {
                $conditions[] = ['expire_time', '>', 0];
                $conditions[] = ['expire_time', '<', time()];
            }
            if ($params['status'] == 1) {
                $conditions[] = ['expire_time', '>', time()];
            }
        }
        return $conditions;
    }
}