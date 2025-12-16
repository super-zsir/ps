<?php

namespace Imee\Service\Operate\Report;

use Imee\Exception\ApiException;
use Imee\Models\Xs\XsAuditReport;
use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xs\XsDelayForbiddenTask;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;
use Imee\Service\StatusService;

class MessageReportService
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
        list($res, $msg, $list) = $this->rpcService->reportList($conditions);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
        if (empty($list['data'])) {
            return $list;
        }

        $bigAreaList = XsBigarea::getAllNewBigArea();

        foreach ($list['data'] as &$item) {
            $item['big_area'] = $bigAreaList[$item['big_area']] ?? '';
            $item['report_user'] = $this->formatUserInfo($item['report_user']);
            $item['target_user'] = $this->formatUserInfo($item['target']);
            $item['target_uid'] = $item['target']['uid'] ?? 0;
            if (isset($item['reason'])) {
                $item['report_message'] = $item['reason']['report_message'] ?? '';
                if ($item['reason']['message_type'] == XsAuditReport::MESSAGE_TYPE_FILE) {
                    if ($this->isImage($item['report_message'])) {
                        $item['report_message'] = $this->setImage($item['report_message']);
                    } else {
                        $item['report_message'] = "<video src='" . $item['report_message'] . "' controls='controls' style='width: 100px; height: 150px'></video>";
                    }
                }
                $item['photos'] = $this->formatPhotos($item['reason']['photos'] ?? []);
                $item['desc'] = $item['reason']['desc'] ?? '';
                $item['send_time'] = Helper::now($item['reason']['send_time'] ?? 0);
                $item['type'] = XsAuditReport::$reasonTypeMap[$item['reason']['type'] ?? 0];
            }
            $item['create_time'] = Helper::now($item['create_time']);
            $item['report_type'] = XsAuditReport::$reportTypeMap[$item['report_type']];
            $item['target_status'] = $this->formatUserInfo($item['target'], 2);
            $item['status_text'] = XsAuditReport::$statusMap[$item['status']];
        }

        return $list;
    }

    private function formatPhotos(array $photos): string
    {
        if (empty($photos)) {
            return '';
        }

        $list = [];
        foreach ($photos as $item) {
            if ($this->isImage($item)) {
                $list[] = $this->setImage($item);
            } else {
                $list[] = "<video src='" . $item . "' controls='controls' style='width: 100px; height: 150px; margin: 5px 5px'></video>";
            }
        }

        $html = implode('', $list);
        return "<div style='width:220px; display: flex;flex-flow: wrap;'>{$html}</div>";
    }

    private function setImage($url)
    {
        return "<a title='点击放大' target='_blank' href='" . $url . "'><img src='" . $url . "' style='width: 100px; height: 150px; margin: 5px 5px;'></a>";
    }

    /**
     * 判断是否为图片
     * @param $url
     * @return bool
     */
    private function isImage($url): bool
    {
        // 使用 getimagesize() 函数获取图像信息
        $imageInfo = @getimagesize($url);

        // 如果获取成功并且返回的第一个和第二个元素（宽度和高度）存在，就是一个有效的图片
        if ($imageInfo !== false && isset($imageInfo[0]) && isset($imageInfo[1])) {
            return true;
        }

        return false;
    }

    private function formatUserInfo(array $user, int $type = 1): string
    {
        if (empty($user)) {
            return '';
        }

        $name = $user['name'] ?? '';
        $level = $user['level'] ?? '';
        $uid = $user['uid'] ?? '';
        $banType = $user['ban_type'] ?? 0;
        $banExpireAt = $user['ban_expire_at'] ?? 0;

        $string = $name . '<br />' . $uid . '<br />' . $level;
        if ($type == 2) {
            $string = XsAuditReport::$banTypeMap[$banType] ?? '';
            if ($banType != 0 && $banExpireAt > 0) {
                $string .= '<br />解封时间：' . Helper::now($banExpireAt);
            }
        }
        return $string;
    }

    private function getConditions(array $params): array
    {
        $conditions = [
            'page'      => intval($params['page'] ?? 1),
            'page_size' => intval($params['limit'] ?? 15),
        ];

        if (isset($params['business_id']) && !empty($params['business_id'])) {
            $conditions['business_id'] = $params['business_id'];
        }
        if (isset($params['big_area']) && !empty($params['big_area'])) {
            $conditions['big_area'] = (int) $params['big_area'];
        }
        if (isset($params['target_uid']) && !empty($params['target_uid'])) {
            $conditions['target'] = (int) $params['target_uid'];
        }
        if (isset($params['status'])) {
            $conditions['status'] = (int) ($params['status'] + 1);
        }
        return $conditions;
    }

    public function getStatusMap()
    {
        return StatusService::formatMap(XsAuditReport::$statusMap, 'label,value');
    }

    public function getOptions()
    {
        $banType = XsAuditReport::$banTypeMap;
        unset($banType[XsAuditReport::BAN_TYPE_DEFAULT]);
        $banType = StatusService::formatMap($banType, 'label,value');
        $duration = StatusService::formatMap(XsAuditReport::$durationMap, 'label,value');
        $isBanDevice = StatusService::formatMap(XsAuditReport::$isBanDeviceMap, 'label,value');
        $syncType = StatusService::formatMap(XsAuditReport::$syncTypeMap, 'label,value');

        return compact('banType', 'duration', 'isBanDevice', 'syncType');
    }

    public function userForbiddenCheck($uid): array
    {
        list($flg, $rec) = $this->rpcService->getUserVip(['uid' => intval($uid)]);
        if (!$flg) {
            throw new ApiException(ApiException::MSG_ERROR, $rec);
        }

        $forbiddenTask = XsDelayForbiddenTask::findOneByWhere([
            ['uid', '=', $uid],
            ['status', '=', XsDelayForbiddenTask::STATUS_NOT_EXECUTED],
            ['start_time', '>', time()],
        ]);

        $startTime = '';
        if (!empty($forbiddenTask)) {
            $startTime = $forbiddenTask['start_time'] ? date('Y-m-d H:i:s', $forbiddenTask['start_time']): '';
        }

        return [
            'uid'                  => $uid,
            'level'                 => $rec['level'] ?? 0,
            'forbidden_delay_hours' => $rec['forbidden_delay_hours'] ?? 18,
            'has_delay_forbidden'  => !empty($forbiddenTask),
            'forbidden_start_time' => $startTime,
        ];
    }

    public function banned(array $params): array
    {
        $this->verifyBanned($params);

        $c = trim($params['c'] ?? '');
        if ($c == 'check') {
            $target = $params['target'] ?? [];
            return $this->userForbiddenCheck($target['uid'] ?? 0);
        }

        $data = [
            'report_id'      => (int)$params['id'],
            'device_id'      => $params['device_id'] ?? '',
            'ban_type'       => (int)$params['ban_type'],
            'sync_type'      => (int)$params['sync_type'],
            'is_ban_device'  => (int)$params['is_ban_device'],
            'reason'         => $params['reason'],
            'notice_msg'     => $params['notice_msg'] ?? '',
            'comment'        => $params['comment'] ?? '',
            'operator'       => Helper::getAdminName($params['admin_id']),
            'duration'       => (int)$params['duration'],
            'report_status'  => XsAuditReport::STATUS_PASS,
            'operator_uid'   => $params['admin_id'],
            'must_forbidden' => !!($params['must_forbidden'] ?? 1)
        ];
        list($res, $msg) = $this->rpcService->banUser($data);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        return ['id' => $data['report_id'], 'after_json' => $data];
    }

    public function reject(array $params): array
    {
        $data = [
            'report_id'     => (int)$params['id'],
            'device_id'     => $params['device_id'] ?? '',
            'notice_msg'    => $params['notice_msg'] ?? '',
            'comment'       => $params['comment'] ?? '',
            'operator'      => Helper::getAdminName($params['admin_id']),
            'report_status' => XsAuditReport::STATUS_REJECT,
            'operator_uid'  => $params['admin_id'],
        ];
        list($res, $msg) = $this->rpcService->banUser($data);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        return ['id' => $data['report_id'], 'after_json' => $data];
    }

    private function verifyBanned(array &$params)
    {
        $banType = (int) $params['ban_type'];
        $oldBanType = (int) $params['target']['ban_type'];
        if ($banType < $oldBanType && $banType != XsAuditReport::BAN_TYPE_UNLOCK_USER) {
            throw new ApiException(ApiException::MSG_ERROR, '当前封禁不生效，请先解封。');
        }

        $duration = (int) $params['duration'];
        if ($banType == XsAuditReport::BAN_TYPE_UNLOCK_USER && $duration > 0){
            throw new ApiException(ApiException::MSG_ERROR, '封禁状态为正常时，封禁时长必须选为正常');
        }

        $isBanDevice = (int) $params['is_ban_device'];
        if ($isBanDevice == XsAuditReport::IS_BAN_DEVICE_YES && $banType != XsAuditReport::BAN_TYPE_BANNED) {
            throw new ApiException(ApiException::MSG_ERROR, '封禁设备时，封禁状态必须为【不可被搜索且禁止登录】');
        }
    }

    public function getUserList(int $uid): array
    {
        list($res, $msg, $list) = $this->rpcService->getUserDeviceInfo($uid);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        return $list;
    }

    public function getLogList(array $params): array
    {
        $conditions = [
            'uid'       => (int)$params['target_uid'],
            'device_id' => $params['device_id'] ?? '',
        ];

        list($res, $msg, $list) = $this->rpcService->banLog($conditions);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
        if (empty($list['data'])) {
            return $list;
        }
        $userList = $this->getUserList($conditions['uid']);

        foreach ($list['data'] as $key => &$item) {
            $item['id'] = $key + 1;
            $reportStatus = $item['report_status'] ?? 0;
            $banType = $item['ban_type'] ?? 0;
            switch ($reportStatus) {
                case XsAuditReport::STATUS_WAIT:
                    $item['ban_type'] = '等待审核';
                    break;
                case XsAuditReport::STATUS_PASS:
                    $item['ban_type']= XsAuditReport::$banTypeMap[$banType] ?? '';
                    break;
                case XsAuditReport::STATUS_REJECT:
                    $item['ban_type'] = '驳回';
                    break;
            }
            $item['is_ban_device'] = XsAuditReport::$isBanDeviceMap[$item['is_ban_device']];
            $syncType = $item['sync_type'];
            $syncTypeText = XsAuditReport::$isBanDeviceMap[$item['sync_type']];
            $item['sync_type'] = [
                'title' => $syncTypeText,
                'value' => $syncTypeText,
            ];
            if ($syncType == XsAuditReport::IS_BAN_DEVICE_YES) {
                $item['sync_type']['type'] = 'manMadeModal';
                $item['sync_type']['modal_id'] = 'ban_user_list';
                $item['sync_type']['params'] = [
                    'uid_list' => $userList
                ];
            }
            $item['update_time'] = Helper::now($item['update_time']);
            $item['duration'] = XsAuditReport::$durationMap[$item['duration']];
        }

        return $list;
    }
}