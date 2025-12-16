<?php

namespace Imee\Service\Operate\Livevideo;

use Imee\Comp\Common\Log\Models\Cms\CmsUser;
use Imee\Exception\ApiException;
use Imee\Models\Xs\XsBmsOperateHistory;
use Imee\Models\Xs\XsBmsRoomTop;
use Imee\Models\Xs\XsChatroom;
use Imee\Models\Xs\XsRoomTopConfig;
use Imee\Models\Xs\XsUserProfile;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;

class LiveVideoService
{
    /**
     * @var PsService $rpcService
     */
    private $rpcService;

    /**
     * @var XsRoomTopConfig $model
     */
    private $model = XsRoomTopConfig::class;

    /**
     * @var XsBmsRoomTop $reasonModel
     */
    protected $reasonModel = XsBmsRoomTop::class;

    /**
     * @var XsBmsOperateHistory $logModel
     */
    protected $logModel = XsBmsOperateHistory::class;

    /**
     * @var XsChatroom $roomModel
     */
    protected $roomModel = XsChatroom::class;

    /**
     * @var XsUserProfile $userModel
     */
    protected $userModel = XsUserProfile::class;

    /**
     * @var CmsUser $adminModel
     */
    protected $adminModel = CmsUser::class;

    private $property;
    private $type;

    public function __construct(int $property, int $type)
    {
        $this->property = $property;
        $this->type = $type;
        $this->rpcService = new PsService();
    }

    public function getListAndTotal(array $params): array
    {
        $conditions = $this->getConditions($params);
        $list = $this->model::getListAndTotal($conditions, '*', 'id desc', $params['page'] ?? 1, $params['limit'] ?? 15);

        if (empty($list['data'])) {
            return $list;
        }

        $ids = Helper::arrayFilter($list['data'], 'id');
        $uids = Helper::arrayFilter($list['data'], 'uid');
        // 获取操作记录
        $logs = $this->logModel::getLatestUpdateLog($this->logModel::ROOM_TOP, $ids);
        $reasons = [];
        // 房间移除时获取房间移除记录
        if ($this->type == $this->model::TYPE_REMOVE) {
            $reasons = $this->reasonModel::getListByTid($ids);
        }
        $propertyMap = $this->model::$propertyMap;
        $property = $propertyMap[$this->property];
        // 获取房间、用户信息
        $rooms = $this->roomModel::getListByUidArrayAndProperty($uids, $property);
        $users = $this->userModel::getUserProfileBatch($uids);
        $now = time();

        foreach ($list['data'] as &$item) {
            $item['minutes'] = $reasons[$item['id']]['minutes'] ?? '';
            $item['reason'] = $reasons[$item['id']]['reason'] ?? '';
            $item['rid'] = $rooms[$item['uid']]['rid'];
            $item['deleted'] = $rooms[$item['uid']]['deleted'] ?? -99;
            $item['update_uname'] = $logs[$item['id']]['update_uname'] ?? '';
            $item['update_time'] = $logs[$item['id']]['dateline'] ?? '';
            $item['uname'] = $users[$item['uid']]['name'] ?? '';
            if ($item['end_time'] <= $now && $item['status'] == $this->model::STATUS_EFFECT) {
                $item['status'] = $this->model::STATUS_FAIL; //失效
            } elseif ($item['start_time'] > $now && $item['status'] == $this->model::STATUS_EFFECT) {
                $item['status'] = $this->model::STATUS_NOT_START; //未开始
            }
            $item['start_time'] = date('Y-m-d H:i:s', $item['start_time']);
            $item['end_time'] = date('Y-m-d H:i:s', $item['end_time']);
        }

        return $list;
    }

    /**
     * 房间置顶和移除
     * @param array $params
     * @return array
     * @throws ApiException
     */
    public function topAndRemove(array $params): array
    {
        $now = time();
        $uid = intval($params['uid'] ?? 0);
        $weight = intval($params['weight'] ?? 0);
        $areaId = intval($params['area_id'] ?? 0);
        $minutes = intval($params['minutes'] ?? 0);
        $startTime = trim($params['start_time'] ?? Helper::now($now));
        $endTime = trim($params['end_time'] ?? Helper::now(($now + $minutes * 60)));
        $reason = trim($params['reason'] ?? '');

        $this->validationUid($uid);
        $this->validateProperty($uid);

        $data = [
            'uid'        => $uid,
            'weight'     => $weight,
            'area_id'    => $areaId,
            'start_time' => strtotime($startTime),
            'end_time'   => strtotime($endTime),
            'status'     => $this->model::STATUS_EFFECT,
            'type'       => $this->type,
            'property'   => $this->property,
        ];

        list($res, $id) = $this->rpcService->createRoomTop($data);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $id);
        }

        $this->addReasonLog($id, $now, $minutes, $reason);
        $this->logModel::insertRows($this->logModel::ROOM_TOP, $id, $data, $params['admin_uid']);
        return ['id' => $id, 'after_json' => $data];
    }

    /**
     * 编辑房间置顶时间
     * @param array $params
     * @return array
     * @throws ApiException
     */
    public function modify(array $params): array
    {
        $now = time();
        $id = intval($params['id'] ?? 0);
        $minutes = intval($params['minutes'] ?? 0);
        $startTime = trim($params['start_time'] ?? Helper::now($now));
        $endTime = trim($params['end_time'] ?? Helper::now(($now + $minutes * 60)));
        $reason = trim($params['reason'] ?? '');

        $data = [
            'id'         => $id,
            'type'       => $this->type,
            'start_time' => strtotime($startTime),
            'end_time'   => strtotime($endTime),
        ];

        $this->type == $this->model::TYPE_REMOVE && $data['weight'] = 0;

        list($res, $msg) = $this->rpcService->editRoomTop($data);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
        $this->addReasonLog($id, $now, $minutes, $reason);
        $this->logModel::insertRows($this->logModel::ROOM_TOP, $id, $data, $params['admin_uid']);
        return ['id' => $id, 'after_json' => $data];
    }

    // 记录移除原因
    private function addReasonLog($id, $now, $minutes, $reason): void
    {
        if ($this->type == $this->model::TYPE_REMOVE) {
            $logData = [
                'start_time' => $now,
                'minutes'    => $minutes,
                'reason'     => $reason,
            ];

            $reason = $this->reasonModel::getInfoByTid($id);
            // 记录房间移除原因
            if ($reason) {
                list($res, $msg) = $this->reasonModel::edit($reason['id'], $logData);
            } else {
                $logData['tid'] = $id;
                list($res, $msg) = $this->reasonModel::add($logData);
            }
            if (!$res) {
                throw new ApiException(ApiException::MSG_ERROR, 'reason log add error, message:' . $msg);
            }
        }
    }

    /**
     * 取消房间置顶
     * @param array $params
     * @return array
     * @throws ApiException
     */
    public function cancel(array $params): array
    {
        $id = intval($params['id'] ?? 0);
        list($res, $msg) = $this->rpcService->cancelRoomTop(['id' => $id]);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        $this->logModel::insertRows($this->logModel::ROOM_TOP, $id, ['status' => $this->model::STATUS_CANCEL], $params['admin_uid']);
        return ['id' => $id, 'after_json' => ['status' => $this->model::STATUS_CANCEL]];
    }

    /**
     * 获取操作记录
     * @param array $params
     * @return array
     */
    public function getHistoryListAndTotal(array $params): array
    {
        $id = intval($params['id'] ?? 0);
        $data = $this->logModel::getHistoryBySid($this->logModel::ROOM_TOP, $id, $params['page'] ?? 1, $params['limit'] ?? 15);
        if (empty($data['data'])) {
            return $data;
        }

        $adminList = $this->adminModel::getUserNameList(Helper::arrayFilter($data['data'], 'update_uid'));
        foreach ($data['data'] as &$v) {
            $v['update_uname'] = $adminList[$v['update_uid']] ?? '';
            if (!empty($v['content'])) {
                $v = array_merge($v, json_decode($v['content'], true));
            }
            $v['dateline'] = Helper::now($v['dateline']);
            if (isset($v['status']) && $v['status'] == 0) {
                $v['status'] = '取消';
            }
        }
        return $data;
    }

    /**
     * 验证uid
     * @param int $uid
     * @return void
     * @throws ApiException
     */
    private function validationUid(int $uid): void
    {
        if (empty($uid) || !$this->userModel::findOne($uid)) {
            throw new ApiException(ApiException::MSG_ERROR, 'uid不存在');
        }
    }

    /**
     * 验证房间属性
     * @param int $uid
     * @return void
     * @throws ApiException
     */
    private function validateProperty(int $uid): void
    {
        $property = $msg = '';
        switch ($this->property) {
            case $this->model::PROPERTY_ROOM_TOP:
                $property = $this->roomModel::PROPERTY_VIP;
                $msg = '该用户没有创建过语音房，请检查后再试';
                break;
            case $this->model::PROPERTY_LIVE_VIDEO_TOP:
                $property = $this->roomModel::PROPERTY_LIVEROOM;
                $msg = '该用户没有创建过视频房，请检查后再试';
                break;
        }
        $room = $this->roomModel::getInfoByUidAndProperty($uid, $property);
        if (!$room) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
    }

    private function getConditions(array $params): array
    {
        $conditions = [
            ['property', '=', $this->property],
            ['type', '=', $this->type],
        ];

        $uid = intval($params['uid'] ?? 0);
        $rid = intval($params['rid'] ?? 0);
        $status = intval($params['status'] ?? -1);
        $areaId = intval($params['area_id'] ?? 0);
        $startTime = trim($params['start_time'] ?? '');
        $endTime = trim($params['end_time'] ?? '');

        $ruid = 0;
        if ($rid) {
            $room = $this->roomModel::findOne($rid);
            $room && $ruid = $room['uid'];
        }

        ($uid || $rid) && $conditions[] = ['uid', '=', $uid ?: $ruid];
        $areaId && $conditions[] = ['area_id', '=', $areaId];
        $startTime && $conditions[] = ['start_time', '>=', strtotime($startTime)];
        $endTime && $conditions[] = ['end_time', '<=', strtotime($endTime)];

        $now = time();
        switch ($status) {
            case $this->model::STATUS_CANCEL:
                $conditions[] = ['status', '=', $status];
                break;
            case $this->model::STATUS_EFFECT:
                $conditions[] = ['status', '=', $status];
                $conditions[] = ['end_time', '>', $now];
                $conditions[] = ['start_time', '<=', $now];
                break;
            case $this->model::STATUS_FAIL:
                $conditions[] = ['status', '=', $this->model::STATUS_EFFECT];
                $conditions[] = ['end_time', '<=', $now];
                break;
            case $this->model::STATUS_NOT_START:
                $conditions[] = ['status', '=', $this->model::STATUS_EFFECT];
                $conditions[] = ['start_time', '>', $now];
                break;
        }
        return $conditions;
    }
}