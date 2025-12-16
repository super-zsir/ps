<?php

namespace Imee\Service\Operate\Reward;

use Imee\Comp\Operate\Auth\Models\Cms\CmsModuleUser;
use Imee\Comp\Operate\Auth\Models\Cms\CmsUser;
use Imee\Exception\ApiException;
use Imee\Exception\Operate\PrettyUserCustomizeException;
use Imee\Helper\Traits\ImportTrait;
use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xs\XsBroker;
use Imee\Models\Xs\XsBrokerUser;
use Imee\Models\Xs\XsCommodityAdmin;
use Imee\Models\Xs\XsUserBigarea;
use Imee\Models\Xs\XsUserProfile;
use Imee\Models\Xsst\BaseModel;
use Imee\Models\Xs\XsstCouponAreaManage;
use Imee\Models\Xs\XsstCouponIssued;
use Imee\Models\Xsst\XsstRewardSendTask;
use Imee\Models\Xsst\XsstRewardSendUser;
use Imee\Models\Xsst\XsstRewardTemplate;
use Imee\Models\Xsst\XsstRewardWhitelist;
use Imee\Service\Domain\Service\Pretty\PrettyUserCustomizeService;
use Imee\Service\Helper;
use Imee\Service\Operate\Background\Custombackground\CustomBgcCardSendService;
use Imee\Service\Operate\Certification\CertificationSendService;
use Imee\Service\Operate\Commodity\CommoditySendService;
use Imee\Service\Operate\Coupon\GameCouponIssuedService;
use Imee\Service\Operate\Emoticons\EmoticonsRewardService;
use Imee\Service\Operate\Medal\MedalIssuedService;
use Imee\Service\Operate\Roomskin\RoomSkinSendService;
use Imee\Service\Operate\Topcard\RoomTopCardSendService;
use Imee\Service\Operate\VipsendService;
use Imee\Service\Operate\Roombackground\BackgroundSendService;
use Imee\Service\StatusService;
use Phalcon\Di;

class RewardSendService
{
    const MAX_FILE_NUMBER = 500;

    use ImportTrait;

    public function getList(array $params): array
    {
        $list = XsstRewardSendTask::getListAndTotal($this->getConditions($params), '*', 'id desc', $params['page'] ?? 1, $params['limit'] ?? 15);

        if (empty($list['data'])) {
            return $list;
        }

        $taskUserList = XsstRewardSendUser::getSendNumByTaskIdList(Helper::arrayFilter($list['data'], 'id'));
        $adminIdArr = array_merge(Helper::arrayFilter($list['data'], 'admin_id'), Helper::arrayFilter($list['data'], 'audit_id'));
        $adminIdArr = array_values(array_filter(array_unique($adminIdArr)));
        $adminList = CmsUser::getUserNameList($adminIdArr);
        $bigAreaList = XsBigarea::getAllNewBigArea();
        foreach ($list['data'] as &$item) {
            $item['audit_name']= $adminList[$item['audit_id']] ?? '';
            $item['admin_name']= $adminList[$item['admin_id']] ?? '';
            $item['task_status']= $item['audit_status'] == XsstRewardSendTask::AUDIT_STATUS_PASS ? (XsstRewardSendTask::$taskStatusMap[$item['task_status']] ?? '') : '';
            $item['audit_status_text']= XsstRewardSendTask::$auditStatusMap[$item['audit_status']] ?? '';
            $item['audit_time'] = $item['audit_time'] ? Helper::now($item['audit_time']) : '';
            $item['create_time'] = Helper::now($item['create_time']);
            $item['is_gift_coupon'] = XsstRewardSendTask::$isGiftCouponMap[$item['is_gift_coupon']] ?? '';
            $item['big_area'] = $bigAreaList[$item['big_area']] ?? '';
            $item['number'] = $taskUserList[$item['id']] ?? 0;
        }

        return $list;
    }

    private function getConditions(array $params): array
    {
        $conditions = [];

        if (isset($params['tid']) && !empty($params['tid'])) {
            $conditions[] = ['tid', '=', $params['tid']];
        }
        if (isset($params['tname']) && !empty($params['tname'])) {
            $conditions[] = ['tname', 'like', $params['tname']];
        }
        if (isset($params['big_area']) && !empty($params['big_area'])) {
            $conditions[] = ['big_area', '=', $params['big_area']];
        }
        if (isset($params['audit_status'])) {
            $conditions[] = ['audit_status', '=', $params['audit_status']];
        }
        if (isset($params['admin_name']) && !empty($params['admin_name'])) {
            $conditions[] = ['admin_id', '=', $params['admin_name']];
        }
        if (isset($params['create_time_sdate']) && !empty($params['create_time_sdate'])) {
            $conditions[] = ['create_time', '>=', strtotime($params['create_time_sdate'])];
        }
        if (isset($params['create_time_edate']) && !empty($params['create_time_edate'])) {
            $conditions[] = ['create_time', '<', strtotime($params['create_time_edate']) + 86400];
        }
        if (isset($params['audit_time_sdate']) && !empty($params['audit_time_sdate'])) {
            $conditions[] = ['audit_time', '>=', strtotime($params['audit_time_sdate'])];
        }
        if (isset($params['audit_time_edate']) && !empty($params['audit_time_edate'])) {
            $conditions[] = ['audit_time', '<', strtotime($params['audit_time_edate']) + 86400];
        }

        return $conditions;
    }

    public function create(array $params): array
    {
        [$task, $uidList] = $this->validation($params);
        $conn = Di::getDefault()->getShared(BaseModel::SCHEMA);
        $conn->begin();
        try {
            list($taskRes, $taskId) = XsstRewardSendTask::add($task);
            if (!$taskRes) {
                throw new ApiException(ApiException::MSG_ERROR, 'task数据添加失败，原因：' . $taskId);
            }
            $uidList = array_map(function (&$item) use ($taskId) {
                $item['task_id']= $taskId;
                return $item;
            }, $uidList);
            list($userRes, $userMsg, $_) = XsstRewardSendUser::addBatch($uidList, 'INSERT IGNORE');
            if (!$userRes) {
                throw new ApiException(ApiException::MSG_ERROR, 'user数据添加失败，原因：' . $userMsg);
            }
            $conn->commit();
        } catch (ApiException $e) {
            $conn->rollback();
            throw new ApiException(ApiException::MSG_ERROR, $e->getMsg());
        }

        $task['uid_list'] = $uidList;
        return ['id' => $taskId, 'after_json' => $task];
    }

    public function auditBatch(array $params, bool $batch = false): array
    {
        [$idArr, $data] = $this->validAudit($params, $batch);
        $conn = Di::getDefault()->getShared(BaseModel::SCHEMA);
        $conn->begin();
        try {
            list($taskRes, $taskMsg, $rows) = XsstRewardSendTask::updateByWhere([['id', 'IN', $idArr]], $data);
            if (!$taskRes) {
                throw new ApiException(ApiException::MSG_ERROR, $taskMsg);
            }
            switch ($data['audit_status']) {
                case XsstRewardSendTask::AUDIT_STATUS_PASS:
                    $this->handleAuditPass($idArr);
                    break;
                case XsstRewardSendTask::AUDIT_STATUS_FAIL:
                    $this->handleAuditFail($idArr);
                    break;
            }
            $conn->commit();
        } catch (ApiException $e) {
            $conn->rollback();
            throw new ApiException(ApiException::MSG_ERROR, $e->getMsg());
        }

        return ['id' => $idArr, 'after_json'=> $data];
    }

    /**
     * 审核成功
     * @param array $idArr
     * @return void
     */
    private function handleAuditPass(array $idArr): void
    {
        // todo 暂时不做处理，采用定时任务下发奖励
    }

    /**
     * 审核不通过处理
     * @param array $idArr
     * @return void
     * @throws ApiException
     */
    private function handleAuditFail(array $idArr): void
    {
        // 审核不通过直接删除下发用户数据
        list($res, $msg, $rows) = XsstRewardSendUser::deleteByWhere([
            ['task_id', 'IN', $idArr]
        ]);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
    }


    private function validation(array $params): array
    {
        $tid = intval($params['tid']);
        $uidList = Helper::formatIdString($params['uid_list']);
        $isNotice = intval($params['is_notice']);
        $source = trim($params['source'] ?? '官方下发');
        $remark = trim($params['remark'] ?? '');
        $adminId = $params['admin_id'];
        $now = time();

        $template = XsstRewardTemplate::findOneByWhere([
            ['id', '=', $tid],
            ['status', '=', XsstRewardTemplate::STATUS_EFFECTIVE]
        ]);
        if (empty($template)) {
            throw new ApiException(ApiException::MSG_ERROR, '奖励模板当前为失效状态，请检查后重试');
        }

        $this->validAdminBigArea($adminId, $template['limit_big_area']);
        $this->validRewardList($template, $uidList);
        $this->validUidList($uidList, $template, $adminId, $now);

        $taskRecord = [
            'tid'            => $tid,
            'tname'          => $template['name'],
            'big_area'       => $template['limit_big_area'],
            'is_gift_coupon' => $this->setIsGiftCoupon($template['reward_list']),
            'is_notice'      => $isNotice,
            'source'         => $source,
            'remark'         => $remark,
            'admin_id'       => $adminId,
            'create_time'    => $now,
        ];

        $uidRecord = [];

        foreach ($uidList as $uid) {
            $uidRecord[] = [
                'uid' => $uid,
                'tid' => $tid,
                'admin_id' => $adminId,
                'dateline' => $now
            ];
        }
        return [$taskRecord, $uidRecord];
    }

    private function validRewardList($template, $uidList): void
    {
        $rewardList = @json_decode($template['reward_list'], true);
        $rewardTypeArr = Helper::arrayFilter($rewardList, 'type');
        // 房间皮肤接口单次最多上传100个uid，这里做下限制
        if (in_array(XsstRewardTemplate::REWARD_TYPE_ROOM_SKIN, $rewardTypeArr) && count($uidList) > 100) {
            throw new ApiException(ApiException::MSG_ERROR, '发放UID超过限制，此模版中包含房间皮肤奖励，每次最多可下发给100个用户');
        }
    }

    /**
     * 验证后台用户大区权限
     * @param $adminId
     * @param $bigArea
     * @return void
     * @throws ApiException
     */
    private function validAdminBigArea($adminId, $bigArea): void
    {
        $bigAreaName = XsBigarea::findFirstBigAreaName($bigArea);
        if (Helper::checkAdminBigArea($adminId, $bigArea)) {
            throw new ApiException(ApiException::MSG_ERROR, sprintf('由于大区权限限制，你无法发放奖励给%s的用户', $bigAreaName['cn_name'] ?? ''));
        }
    }

    /**
     * 审核验证
     * @param array $params
     * @return array
     * @throws ApiException
     */
    private function validAudit(array $params, bool $batch = false): array
    {
        $idArr = Helper::formatIdString($params['id'] ?? '');
        $adminId = $params['admin_id'];
        $auditStatus = intval($params['audit_status'] ?? 0);
        $now = time();

        if (empty($auditStatus) || !in_array($auditStatus, [XsstRewardSendTask::AUDIT_STATUS_PASS, XsstRewardSendTask::AUDIT_STATUS_FAIL])) {
            throw new ApiException(ApiException::MSG_ERROR, '审核状态错误');
        }

        if (empty($idArr)) {
            throw new ApiException(ApiException::MSG_ERROR, '请选择需要审核的任务');
        }

        $taskList = XsstRewardSendTask::getListByWhere([
            ['id', 'IN', $idArr]
        ], 'id, audit_status, is_gift_coupon, tid');

        // 验证是否拥有审核权限
        $isGiftCouponArr = array_column($taskList, 'is_gift_coupon');
        if ($auditStatus == XsstRewardSendTask::AUDIT_STATUS_PASS && in_array(XsstRewardSendTask::IS_GIFT_COUPON_YES, $isGiftCouponArr)) {
            $this->validAuditWhitelist($adminId);
        }
        // 获取模版奖励
        $templateList = XsstRewardTemplate::getListByWhere([['id', 'IN', Helper::arrayFilter($taskList, 'tid')]], 'id, limit_object, limit_big_area, reward_list, max_send_num');
        $templateList = array_column($templateList, null, 'id');
        $couponRewardList = [];
        $errorTaskArr = $errorTidArr = [];

        $purviews = CmsModuleUser::getUserAllAction($adminId);

        foreach ($taskList as $task) {
            $template = $templateList[$task['tid']] ?? [];
            if (empty($template)) {
                $errorTidArr[] = $task['tid'];
                continue;
            }
            if ($task['audit_status'] != XsstRewardSendTask::AUDIT_STATUS_WAIT) {
                $errorTaskArr[] = $task['tid'];
            }
            // 验证后台用户大区权限
            $this->validAdminBigArea($adminId, $template['limit_big_area']);
            // 只有成功的时候在验证下面信息
            if ($auditStatus == XsstRewardSendTask::AUDIT_STATUS_PASS) {
                // 验证uid和模版之间的内容是否冲突
                $uidList = XsstRewardSendUser::getListByTaskId($task['id']);
                $uidList && $this->validUidList($uidList, $template, $adminId, $now, true);
                // 是否存在优惠券
                $rewardList = @json_decode($template['reward_list'], true);

                foreach ($rewardList as $rewardItem) {
                    if ($rewardItem['type'] == XsstRewardTemplate::REWARD_TYPE_GAME_COUPON) {
                        $auth = $batch ? 'operate/coupon/gamecouponissued.auditBatch' : 'operate/coupon/gamecouponissued.audit';
                        if (!in_array($auth, $purviews)) {
                            $str = $batch ? '批量' : '';
                            throw new ApiException(ApiException::MSG_ERROR, "下发失败，暂无游戏优惠券下发{$str}审核权限，请先申请");
                        }
                        $couponRewardList[] = $rewardItem;
                    }
                }
            }
        }

        if ($errorTidArr) {
            throw new ApiException(ApiException::MSG_ERROR, sprintf('模版%s的不存在，请检查后重试', implode(',', $errorTidArr)));
        }
        if ($errorTaskArr) {
            throw new ApiException(ApiException::MSG_ERROR, sprintf('任务%s的审核状态不正确，请检查后重试', implode(',', $errorTaskArr)));
        }

        // 验证优惠券余额是否充足
        $couponRewardList && $this->validCouponPrice($couponRewardList);

        $update = [
            'audit_status' => $auditStatus,
            'audit_id'     => $adminId,
            'audit_time'   => $now,
        ];

        return [$idArr, $update];
    }

    /**
     * 验证优惠券余额是否充足
     * @param array $couponRewardList
     * @return void
     * @throws ApiException
     */
    private function validCouponPrice(array $couponRewardList): void
    {
        $couponBigAreaPriceArr = [];
        $couponService = new GameCouponIssuedService();
        $bigAreaIdArr = Helper::arrayFilter($couponRewardList, 'big_area');
        // 获取待审核的下发金额
        $bigAreaWaitPrice = XsstCouponIssued::getBigAreaWaitPriceList($bigAreaIdArr);
        // 获取优惠券大区金额
        $couponAreaManageList = XsstCouponAreaManage::getListByBigArea($bigAreaIdArr);
        $errorBigArea = [];

        foreach ($couponRewardList as $couponReward) {
            // 获取档位
            list($amountRes, $amount) = $couponService->getCouponAmount($couponReward['id']);
            if (!$amountRes) {
                throw new ApiException(ApiException::MSG_ERROR, $amount);
            }
            $price = $amount * $couponReward['send_num'];

            // 大区待审核金额只需加一次即可
            if (isset($couponBigAreaPriceArr[$couponReward['big_area']])) {
                $couponBigAreaPriceArr[$couponReward['big_area']] += $price;
            } else {
                $couponBigAreaPriceArr[$couponReward['big_area']] = $price + ($bigAreaWaitPrice[$couponReward['big_area']] ?? 0);
            }
        }
        foreach ($couponBigAreaPriceArr as $bigArea => $waitPrice) {
            if (!isset($couponAreaManageList[$bigArea]) || ($couponAreaManageList[$bigArea] < $waitPrice)) {
                $errorBigArea[] = $bigArea;
            }
        }

        if ($errorBigArea) {
            throw new ApiException(ApiException::MSG_ERROR, sprintf('游戏优惠券的%s账户余额不足，请充值后再进行审核', XsBigarea::formatBigAreaName($errorBigArea)));
        }
    }

    /**
     * 验证审核白名单
     * @param int $adminId
     * @return void
     * @throws ApiException
     */
    public function validAuditWhitelist(int $adminId): void
    {
        $whitelist = XsstRewardWhitelist::hasWhiteListUid(XsstRewardWhitelist::TYPE_REWARD_SEND_AUDIT, $adminId);

        if (empty($whitelist)) {
            throw new ApiException(ApiException::MSG_ERROR, '你无法审核发放包含礼物/礼物优惠券的奖励');
        }
    }

    /**
     * uid相关检验
     * @param array $uidList
     * @param array $template
     * @param int $adminId
     * @param int $now
     * @param bool $isAudit
     * @return void
     * @throws ApiException
     */
    private function validUidList(array $uidList, array $template, int $adminId, int $now, bool $isAudit = false): void
    {
        if (count($uidList) > self::MAX_FILE_NUMBER) {
            throw new ApiException(ApiException::MSG_ERROR, sprintf('当前最大限制为%d个uid, 请分批发放', self::MAX_FILE_NUMBER));
        }

        // 验证用户UID
        $errorUidList = XsUserProfile::checkUid($uidList);
        if ($errorUidList) {
            throw new ApiException(ApiException::MSG_ERROR, sprintf('用户：%s不存在，请修改后重试', implode(',', array_unique($errorUidList))));
        }

        $errorBigAreaList = XsUserBigarea::checkUidBigArea($uidList, $template['limit_big_area']);
        if ($errorBigAreaList) {
            throw new ApiException(ApiException::MSG_ERROR, sprintf('%s的运营大区与奖励模板大区不一致，请修改后重试', implode(',', array_unique($errorBigAreaList))));
        }

        $errorObjectList = [];
        switch ($template['limit_object']) {
            case XsstRewardTemplate::LIMIT_OBJECT_BROKER_MASTER:
                $errorObjectList = XsBroker::checkUidBroker($uidList);
                break;
            case XsstRewardTemplate::LIMIT_OBJECT_ANCHOR:
                $errorObjectList = XsBrokerUser::checkUidBroker($uidList, true, false);
                break;
            case XsstRewardTemplate::LIMIT_OBJECT_NON_BROKER_MEMBER:
                $errorObjectList = XsBrokerUser::checkUidBroker($uidList, false, true);
                break;
        }

        if ($errorObjectList) {
            throw new ApiException(ApiException::MSG_ERROR, sprintf('%s的身份类型不是%s，请修改后重试', implode(',', array_unique($errorObjectList)), XsstRewardTemplate::$limitObjectMap[$template['limit_object']]));
        }

        // 白名单内后台用户下发时不需要校验
        $whitelist = XsstRewardWhitelist::hasWhiteListUid(XsstRewardWhitelist::TYPE_REWARD_SEND, $adminId);
        if (empty($whitelist) && $template['max_send_num'] >= 0) {
            $uidSendNumList = XsstRewardSendUser::getUidSendNumByTidList($template['id'], $uidList, $now);
            $errorUidSendNum = [];
            foreach ($uidSendNumList as $uid => $num) {
                if ($isAudit) {
                    $num > $template['max_send_num'] && $errorUidSendNum[] = $uid;
                } else {
                    $num >= $template['max_send_num'] && $errorUidSendNum[] = $uid;
                }
            }
            if ($errorUidSendNum) {
                throw new ApiException(ApiException::MSG_ERROR, sprintf('%s的30天发放次数已经达到了上限，无法发放，请检查后重试', implode(',', array_unique($errorUidSendNum))));
            }
        }
    }

    /**
     * 奖励是否包含优惠券/礼物
     * @param string $rewardList
     * @return bool
     */
    private function setIsGiftCoupon(string $rewardList): int
    {
        $rewardList = @json_decode($rewardList, true);
        foreach ($rewardList as $reward) {
            if ($reward['type'] == XsstRewardTemplate::REWARD_TYPE_COMMODITY) {
                $commodity = XsCommodityAdmin::getInfo($reward['id']);
                if ($commodity && ($commodity['type'] == 'gift' || $commodity['type'] == 'coupon')) {
                    return XsstRewardSendTask::IS_GIFT_COUPON_YES;
                }
            }
        }

        return XsstRewardSendTask::IS_GIFT_COUPON_NO;
    }

    /**
     * 获取上传uid
     * @return string
     * @throws ApiException
     */
    public function importUid(): string
    {
        list($res, $msg, $data) = $this->uploadCsv(['uid']);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
        $uidList = Helper::arrayFilter($data['data'], 'uid');

        if (empty($uidList)) {
            throw new ApiException(ApiException::MSG_ERROR, '上传uid数据为空');
        }

        return implode(',', $uidList);
    }

    /**
     * 获取审核状态(筛选)
     * @return array
     */
    public function getAuditStatusFilterMap(): array
    {
        return StatusService::formatMap(XsstRewardSendTask::$auditStatusMap, 'label,value');
    }

    /**
     * 获取审核状态
     * @return array
     */
    public function getAuditStatusMap(): array
    {
        $status = XsstRewardSendTask::$auditStatusMap;
        unset($status[XsstRewardSendTask::AUDIT_STATUS_WAIT]);
        return StatusService::formatMap($status, 'label,value');
    }

    /**
     * 获取是否发送IM通知
     * @return array
     */
    public function getIsNoticeMap(): array
    {
        return StatusService::formatMap(XsstRewardSendTask::$isNoticeMap, 'label,value');
    }

    /**
     * 获取奖励模版
     * @return array
     */
    public function getRewardTemplateMap(): array
    {
        return StatusService::formatMap(XsstRewardTemplate::getListMap(), 'label,value');
    }

    public function getUserList(array $params): array
    {
        $list = XsstRewardSendUser::getListAndTotal($this->getUserConditions($params), '*', 'id desc', $params['page'] ?? 1, $params['limit'] ?? 15);

        if (empty($list['data'])) {
            return $list;
        }

        foreach ($list['data'] as &$item) {
            $item['status'] = XsstRewardSendUser::$statusMap[$item['status']];
            $item['send_time'] = $item['send_time'] ? Helper::now($item['send_time']) : '';
            $item['error_msg'] = str_replace('&quot;', '', $item['error_msg']);
            $item['reward_send_status'] = $this->formatRewardSendStatus($item['reward_send_status'] ?? '');
        }

        return $list;
    }

    public function getUserConditions(array $params): array
    {
        $conditions = [
            ['task_id', '=', intval($params['id'] ?? 0)]
        ];

        if (isset($params['uid']) && !empty($params['uid'])) {
            $conditions[] = ['uid', '=', $params['uid']];
        }
        if (isset($params['status']) && !empty($params['status'])) {
            $conditions[] = ['status', '=', $params['status']];
        }

        return $conditions;
    }

    private function formatRewardSendStatus(string $rewardSendStatusJson): string
    {
        if (empty($rewardSendStatusJson)) {
            return '';
        }
        $statusMap = ['失败','成功'];
        $statusArr = [];
        $rewardSendStatusJson = XsstRewardSendUser::formatJson($rewardSendStatusJson);
        foreach ($rewardSendStatusJson as $index => $status) {
            $statusArr[] = sprintf('奖品%d: %s', $index, $statusMap[$status]);
        }

        return implode('<br />', $statusArr);
     }

    /**
     * 获取用户发放状态
     * @return array
     */
    public function getUserStatusMap(): array
    {
        return StatusService::formatMap(XsstRewardSendUser::$statusMap, 'label,value');
    }

    /**
     * 发放奖励
     * @param array $task
     * @param array $uidList
     * @return void
     */
    public function sendReward(array $task, array $uidList = [])
    {
        $now = time();
        list($res, $msg) = XsstRewardSendTask::edit($task['id'], ['task_status' => XsstRewardSendTask::TASK_STATUS_SENDING]);
        if (!$res) {
            $this->console(sprintf('task %s发放中修改状态失败，原因：%s', $task['id'], $msg));
        }
        $template = XsstRewardTemplate::findOne($task['tid']);
        if (empty($uidList)) {
            $uidList = XsstRewardSendUser::getListByTaskId($task['id']);
        }
        $rewardList = @json_decode($template['reward_list'], true);
        $errorMsg = [];

        // 获取用户下发状态，下发为原子操作只取第一个用户的下发状态即可
        $rewardSendStatus = XsstRewardSendUser::findRewardSendUser($task['id'], $uidList[0]);
        foreach ($rewardList as $reward) {
            if (isset($rewardSendStatus[$reward['index']]) && $rewardSendStatus[$reward['index']] == 1) {
                // 如果这个奖励已经发送成功过了，跳过
                continue;
            }
            try {
                switch ($reward['type']) {
                    case XsstRewardTemplate::REWARD_TYPE_COMMODITY:
                        $service = new CommoditySendService();
                        $data = [
                            'uid_list' => $uidList,
                            'cid'      => $reward['id'],
                            'num'      => $reward['send_num'],
                            'remark'   => $task['remark'],
                            'source'   => $task['source'],
                            'admin_id' => $task['audit_id']
                        ];
                        $service->sendAndAudit($data);
                        break;
                    case XsstRewardTemplate::REWARD_TYPE_VIP:
                        $service = new VipsendService();
                        $data = [
                            'vip_level' => (int)$reward['id'],
                            'vip_day'   => (int)$reward['valid_days'],
                            'uids'      => implode(',', $uidList),
                            'remark'    => $task['remark'],
                            'type'      => $reward['give_type'],
                            'send_num'  => $reward['send_num'],
                            'admin_id'  => $task['audit_id']
                        ];
                        $service->create($data);
                        break;
                    case XsstRewardTemplate::REWARD_TYPE_OPTIONAL_PRETTY:
                        $service = new PrettyUserCustomizeService();
                        $data = [
                            'uid_str'                  => implode(',', $uidList),
                            'customize_pretty_id'      => (int)$reward['id'],
                            'pretty_validity_day'      => (int)$reward['valid_days'],
                            'qualification_expire_day' => (int)$reward['use_valid_days'],
                            'remark'                   => $task['remark'],
                            'give_type'                => (int)$reward['give_type'],
                            'send_num'                 => (int)$reward['send_num'],
                            'admin_id'                 => $task['audit_id']
                        ];
                        $service->create($data);
                        break;
                    case XsstRewardTemplate::REWARD_TYPE_MEDAL:
                        $service = new MedalIssuedService();
                        $data = [
                            'uid'         => implode(',', $uidList),
                            'medal'       => (int)$reward['id'],
                            'expire_time' => (int)$reward['valid_days'],
                            'reason'      => $task['remark'],
                            'source'      => $task['source'],
                            'admin_id'    => $task['audit_id']
                        ];
                        list($res, $msg) = $service->add($data);
                        if (!$res) {
                            throw new ApiException(ApiException::MSG_ERROR, $msg);
                        }
                        break;
                    case XsstRewardTemplate::REWARD_TYPE_CERTIFICATION_SIGN:
                        $service = new CertificationSendService();
                        $data = [
                            'uid'        => implode(',', $uidList),
                            'bigarea_id' => $task['big_area'],
                            'cer_id'     => (int)$reward['id'],
                            'num'        => $reward['send_num'],
                            'valid_day'  => $reward['valid_days'],
                            'remark'     => $task['remark'],
                            'content'    => $reward['content'],
                            'admin_id'   => $task['audit_id'],
                            'tid_index'  => $task['id'] . '_' . $reward['index']
                        ];
                        $service->sendAndAudit($data);
                        break;
                    case XsstRewardTemplate::REWARD_TYPE_ROOM_BACKGROUND:
                        $service = new BackgroundSendService();
                        $data = [
                            'uid'      => implode(',', $uidList),
                            'bg_id'    => (int)$reward['id'],
                            'duration' => (int)$reward['valid_days'],
                            'admin_id' => $task['audit_id'],
                            'source'   => $task['source'],
                        ];
                        list($res, $msg) = $service->send($data);
                        if (!$res) {
                            throw new ApiException(ApiException::MSG_ERROR, $msg);
                        }
                        break;
                    case XsstRewardTemplate::REWARD_TYPE_ROOM_CUSTOM_BACKGROUND_CARD:
                        $service = new CustomBgcCardSendService();
                        $data = [
                            'uid'          => implode(',', $uidList),
                            'valid_term'   => (int)$reward['valid_days'],
                            'reason'       => $task['remark'],
                            'can_transfer' => (int)$reward['give_type'],
                            'num'          => (int)$reward['send_num'],
                            'admin_id'     => $task['audit_id'],
                        ];
                        $service->send($data);
                        break;
                    case XsstRewardTemplate::REWARD_TYPE_ROOM_TOP_CARD:
                        $service = new RoomTopCardSendService();
                        $data = [
                            'uid'              => implode(',', $uidList),
                            'room_top_card_id' => (int)$reward['id'],
                            'num'              => (int)$reward['send_num'],
                            'expired_time'     => (int)$reward['valid_days'],
                            'remark'           => $task['remark'],
                            'admin_id'         => $task['audit_id'],
                        ];
                        list($res, $msg) = $service->create($data);
                        if (!$res) {
                            throw new ApiException(ApiException::MSG_ERROR, $msg);
                        }
                        break;
                    case XsstRewardTemplate::REWARD_TYPE_ROOM_SKIN:
                        $service = new RoomSkinSendService();
                        $data = [
                            'uid'       => implode(',', $uidList),
                            'commodity' => [
                                [
                                    'skin_id'        => (int)$reward['id'],
                                    'effective_time' => (int)$reward['valid_days']
                                ]
                            ],
                            'remarks'   => $task['remark'],
                            'admin_id'  => $task['audit_id'],
                        ];
                        $service->send($data);
                        break;
                    case XsstRewardTemplate::REWARD_TYPE_GAME_COUPON:
                        $service = new GameCouponIssuedService();
                        $data = [
                            'uid_list'    => $uidList,
                            'bigarea_id'  => $task['big_area'],
                            'coupon_id'   => $reward['id'],
                            'num'         => $reward['send_num'],
                            'expire_time' => $reward['expire'],
                            'note'        => $task['remark'],
                            'admin_id'    => $task['audit_id']
                        ];
                        $service->addAndAudit($data);
                        break;
                    case XsstRewardTemplate::REWARD_TYPE_EMOTICONS:
                        $service = new EmoticonsRewardService();
                        $data = [
                            'uids'         => implode(',', $uidList),
                            'emoticons_id' => (int)$reward['id'],
                            'reward_time'  => (int)$reward['valid_days'],
                            'admin_id'    => $task['audit_id'],
                            'comment'      => $task['remark'],
                        ];
                        $service->create($data);
                        break;
                    default:
                        // todo 待新增
                }
                $rewardSendStatus[$reward['index']] = 1;
            } catch (ApiException $e) {
                $this->console($e->getMsg());
                $rewardSendStatus[$reward['index']] = 0;
                $errorMsg[$reward['index']] = $e->getMsg();
            } catch (PrettyUserCustomizeException $e) {
                $this->console($e->getMessage());
                $rewardSendStatus[$reward['index']] = 0;
                $errorMsg[$reward['index']] = $e->getMessage();
            } catch (\Exception $e) {
                $this->console($e->getMessage());
                $rewardSendStatus[$reward['index']] = 0;
                $errorMsg[$reward['index']] = $e->getMessage();
            }
            usleep(1000 * 100);
        }

        $userStatus = XsstRewardSendUser::STATUS_SUCCESS;
        if ($errorMsg) {
            $userStatus = XsstRewardSendUser::STATUS_FAIL;
        }

        $updateUser = [
            'reward_send_status' => json_encode($rewardSendStatus, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'error_msg'          => json_encode($errorMsg, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'status'             => $userStatus,
            'send_time'          => $now,
            'dateline'           => $now,
        ];

        $updateConditions = [
            ['task_id', '=', $task['id']],
            ['uid', 'IN', $uidList]
        ];

        list($res, $msg, $_) = XsstRewardSendUser::updateByWhere($updateConditions, $updateUser);
        if (!$res) {
            $this->console('用户更新状态失败，原因：' . $msg);
        }
        usleep(1000 * 100);
        // 更新任务状态
        $this->updateTaskStatus($task['id']);

        $this->console(sprintf('task %s reward send done', $task['id']));
    }

    /**
     * 更新任务状态
     * @param int $taskId
     * @return void
     */
    public function updateTaskStatus(int $taskId): void
    {
        $taskSuccess = XsstRewardSendUser::findTaskSendStatus($taskId, XsstRewardSendUser::STATUS_SUCCESS);
        $taskError = XsstRewardSendUser::findTaskSendStatus($taskId, XsstRewardSendUser::STATUS_FAIL);

        $taskStatus = XsstRewardSendTask::TASK_STATUS_SUCCESS;

        if ($taskError) {
            $taskStatus = XsstRewardSendTask::TASK_STATUS_FAIL;
            $taskSuccess && $taskStatus = XsstRewardSendTask::TASK_STATUS_PART_SUCCESS;
        }
        $updateTask = [
            'task_status' => $taskStatus
        ];
        list($taskRes, $msg) = XsstRewardSendTask::edit($taskId, $updateTask);
        if (!$taskRes) {
            $this->console('任务更新状态失败，原因：' . $msg);
        }
    }

    /**
     * 记录下发日志
     * @param string $message
     * @return void
     */
    private function console(string $message): void
    {
        $file = '/tmp/rewardsend.log';
        file_put_contents($file, $message . PHP_EOL, FILE_APPEND);
    }

    /**
     * 发放重试
     * @param array $params
     * @return array
     * @throws ApiException
     */
    public function retrySend(array $params): array
    {
        $uidList = $params['uid'] ?? [];
        $id = $params['id'] ?? 0;
        $adminId = $params['admin_id'];

        if (empty($uidList) || empty($id)) {
            throw new ApiException(ApiException::MSG_ERROR, 'Param not valid');
        }

        $task = XsstRewardSendTask::findOne($id);
        if (empty($task) || !in_array($task['task_status'], [XsstRewardSendTask::TASK_STATUS_PART_SUCCESS, XsstRewardSendTask::TASK_STATUS_FAIL])) {
            throw new ApiException(ApiException::MSG_ERROR, 'task status error');
        }

        $task['is_gift_coupon'] && $this->validAuditWhitelist($adminId);

        $uidList = XsstRewardSendUser::getListByWhere([
            ['uid', 'IN', $uidList],
            ['task_id', '=', $task['id']],
            ['status', '=', XsstRewardSendUser::STATUS_FAIL]
        ], 'uid');

        $uidList = Helper::arrayFilter($uidList, 'uid');
        $diff = array_diff($uidList, $uidList);
        if ($diff) {
            throw new ApiException(ApiException::MSG_ERROR, sprintf('user:%s send status error', implode(',', $diff)));
        }


        $this->sendReward($task, $uidList);

        return ['id' => $id, 'after_json' => ['uid_list' => $uidList]];
    }
}