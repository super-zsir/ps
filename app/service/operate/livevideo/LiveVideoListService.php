<?php

namespace Imee\Service\Operate\Livevideo;

use Imee\Comp\Common\Log\Models\Cms\CmsUser;
use Imee\Exception\ApiException;
use Imee\Models\Xs\XsBanRoomLog;
use Imee\Models\Xs\XsBmsVideoLiveStopLog;
use Imee\Models\Xs\XsChatroom;
use Imee\Models\Xs\XsUserBigarea;
use Imee\Models\Xs\XsUserProfile;
use Imee\Models\Xs\XsVideoLiveSessionLog;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;
use Imee\Service\StatusService;

class LiveVideoListService
{
    /**
     * @var XsVideoLiveSessionLog $model
     */
    private $model = XsVideoLiveSessionLog::class;

    /**
     * @var XsUserBigarea $bigAreaModel
     */
    private $bigAreaModel = XsUserBigarea::class;

    /**
     * @var XsChatroom $chatroomModel
     */
    private $chatroomModel = XsChatroom::class;

    /**
     * @var XsUserProfile $userModel
     */
    private $userModel = XsUserProfile::class;

    /**
     * @var XsBmsVideoLiveStopLog $logModel
     */
    private $logModel = XsBmsVideoLiveStopLog::class;

    /**
     * @var CmsUser $adminModel
     */
    private $adminModel = CmsUser::class;

    /**
     * @var XsBanRoomLog $forbiddenModel
     */
    private $forbiddenModel = XsBanRoomLog::class;

    /**
     * 获取总条数
     * @param $params
     * @param $order
     * @param $offset
     * @param $limit
     * @return array
     */
    public function getListAndTotal(array $params): array
    {
        $fromTableName = $this->model::getTableName();
        $toTableName = $this->bigAreaModel::getTableName();
        $conditions = $this->getCondition($params, $fromTableName, $toTableName);
        $joinCondition = "{$fromTableName}.uid = {$toTableName}.uid";
        $result = $this->model::getListJoinBigArea($conditions, $joinCondition, $params['page'] ?? 1, $params['limit'] ?? 15);

        $chatroom = $this->chatroomModel::getInfoBatch(Helper::arrayFilter($result['data'], 'rid'), ['rid', 'name']);
        $userInfo = $this->userModel::getUserProfileBatch(Helper::arrayFilter($result['data'], 'uid'));
        foreach ($result['data'] as &$v) {
            if ($v['state'] == $this->model::STATE_END && $v['end_type'] == 1) {
                $v['state'] = $this->model::STATE_STOP;
            }
            $v['start_time'] = $v['start_time'] ? Helper::now($v['start_time']) : '-';
            $v['end_time'] = $v['end_time'] ? Helper::now($v['end_time']) : '-';
            $v['room_title'] = $chatroom[$v['rid']]['name'] ?? '-';
            $v['nickname'] = $userInfo[$v['uid']]['name'] ?? '-';
        }
        return $result;
    }

    /**
     * 过滤查询条件
     * @param array $params
     * @return array
     */
    private function getCondition(array $params, string $fromTableName, string $toTableName): array
    {
        $uid = intval($params['uid'] ?? 0);
        $sessionId = intval($params['session_id'] ?? 0);
        $state = intval($params['state'] ?? -1);
        $bigAreaId = intval($params['bigarea_id'] ?? 0);

        $conditions = [];
        $uid && $conditions[] = ["{$fromTableName}.uid", '=', $params['uid']];
        $sessionId && $conditions[] = ["{$fromTableName}.session_id", '=', $params['session_id']];
        $bigAreaId && $conditions[] = ["{$toTableName}.bigarea_id", '=', $params['bigarea_id']];

        if ($state > -1) {
            if ($state == $this->model::STATE_STOP) {
                $conditions[] = ["{$fromTableName}.state", '=', $this->model::STATE_END];
                $conditions[] = ["{$fromTableName}.end_type", '=', 1];
            } else {
                $conditions[] = ["{$fromTableName}.state", '=', $state];
                $conditions[] = ["{$fromTableName}.end_type", '=', 0];
            }
        }

        return $conditions;
    }

    /**
     * 中断直播
     * @param array $params
     * @return array[]
     * @throws ApiException
     */
    public function stop(array $params): array
    {
        $rid = intval($params['rid'] ?? 0);
        $uid = intval($params['uid'] ?? 0);
        $reason = intval($params['reason'] ?? 0);

        $data = [
            'rid'    => $rid,
            'uid'    => $uid,
            'reason' => $this->model::REASON_PREFIX . $reason,
        ];
        // 调用rpc中断直播
        list($res, $msg) = (new PsService())->videoLiveStopModify($data);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
        // 记录中断直播日志
        $this->logModel::addStopLog($params);

        return ['after_json' => $data];
    }

    /**
     * 封禁操作
     * @param array $params
     * @return array[]
     * @throws ApiException
     */
    public function forbidden(array $params): array
    {
        $rid = intval($params['rid'] ?? 0);
        $deleted = intval($params['deleted'] ?? 0);
        $remark = intval($params['remark'] ?? '');
        $duration = intval($params['duration'] ?? 0);
        $reason = intval($params['reason'] ?? 0);

        $data = [
            'rid'       => $rid,
            'duration'  => $duration,
            'reason'    => $reason,
            'remark'    => $remark,
            'admin_uid' => $params['admin_uid']
        ];

        $actionMap = $this->forbiddenModel::$actionMap;
        $action = $actionMap[$deleted] ?? '';

        if (empty($action)) {
            throw new ApiException(ApiException::MSG_ERROR, '操作不存在');
        }

        list($res, $msg) = (new PsService())->$action($data);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        return ['after_json' => $data];
    }

    /**
     * 中断直播记录
     * @param $params
     * @return array
     */
    public function getHistoryListAndTotal($params): array
    {
        $sid = intval($params['id'] ?? 0);
        $type = intval($params['type'] ?? 0);

        $condition = [
            ['sid', '=', $sid],
            ['type', '=', $type]
        ];
        $res = $this->logModel::getListAndTotal($condition, 'admin_uid,reason,remark,create_time', 'create_time desc', $params['page'] ?? 1, $params['limit'] ?? 1);
        $adminList = $this->adminModel::getUserNameList(Helper::arrayFilter($res['data'], 'admin_uid'));
        foreach ($res['data'] as &$v) {
            $v['admin_name'] = $adminList[$v['admin_uid']] ?? '';
            $v['reason'] = $this->forbiddenModel::$reasonMap[$v['reason']] ?? '-';
            $v['create_time'] = $v['create_time'] ? Helper::now($v['create_time']) : '-';
        }
        return $res;
    }

    /**
     * 获取封禁记录
     * @param array $params
     * @return array
     */
    public function getForbiddenLog(array $params): array
    {
        $rid = intval($params['rid'] ?? 0);
        $list = $this->forbiddenModel::getListByWhere([['rid', '=', $rid]], '*', 'id desc', 50);
        $adminList = $this->adminModel::getUserNameList(Helper::arrayFilter($list, 'admin_id'));
        foreach ($list as &$item) {
            $item['admin'] = $adminList[$item['admin_id']] ?? '';
            $item['dateline'] = Helper::now($item['dateline']);
            $item['dur'] = $item['end_time'] - $item['start_time'] > 0 ? ($item['end_time'] - $item['start_time'] . "s") : '-';
            $item['reason'] = $this->forbiddenModel::$reasonMap[$item['reason']] ?? '-';
            $item['deleted'] = $item['op'] == 1 ? '封禁' : '解封';
        }
        return $list;
    }

    /**
     * 封禁枚举获取
     * @return array
     */
    public function getForbiddenOptions(): array
    {
        $deleted = StatusService::formatMap($this->forbiddenModel::$deletedMap);
        $duration = StatusService::formatMap($this->forbiddenModel::$durationMap);
        $reason = StatusService::formatMap($this->forbiddenModel::$reasonMap);

        return compact('duration', 'reason', 'deleted');
    }

    /**
     * 获取直播状态
     * @return array
     */
    public function getStateMap(): array
    {
        return StatusService::formatMap($this->model::$stateMap);
    }

    /**
     * 获取封禁原因
     * @return array
     */
    public function getReasonMap(): array
    {
        return StatusService::formatMap($this->model::$reasonMap);
    }
}