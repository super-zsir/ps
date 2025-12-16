<?php

namespace Imee\Service\Operate\Chatroom;

use Imee\Comp\Operate\Auth\Models\Cms\CmsUser;
use Imee\Exception\ApiException;
use Imee\Models\Config\BbcChatroomModuleTag;
use Imee\Models\Config\BbcSettlementChannel;
use Imee\Models\Redis\ChatroomRedis;
use Imee\Models\Xs\XsBanRoomLog;
use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xs\XsBmsVideoLiveStopLog;
use Imee\Models\Xs\XsBroker;
use Imee\Models\Xs\XsBrokerUser;
use Imee\Models\Xs\XsChatroom;
use Imee\Models\Xs\XsChatroomAuctionConfig;
use Imee\Models\Xs\XsChatroomBackground;
use Imee\Models\Xs\XsChatroomModuleFactory;
use Imee\Models\Xs\XsUserBigarea;
use Imee\Models\Xsst\XsstChatroomAdminLog;
use Imee\Models\Xsst\XsstChatroomDefaultCover;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;
use Imee\Service\StatusService;

class ChatroomService
{
    /**
     * @var XsChatroom $model
     */
    private $model = XsChatroom::class;

    /**
     * @var XsChatroomAuctionConfig $configModel
     */
    private $configModel = XsChatroomAuctionConfig::class;

    /**
     * @var XsChatroomModuleFactory $factoryModel
     */
    private $factoryModel = XsChatroomModuleFactory::class;

    /**
     *
     * @var XsChatroomBackground $backgroundModel
     */
    private $backgroundModel = XsChatroomBackground::class;

    /**
     * @var BbcChatroomModuleTag $tagModel
     */
    private $tagModel = BbcChatroomModuleTag::class;

    /**
     * @var BbcSettlementChannel $channelModel
     */
    private $channelModel = BbcSettlementChannel::class;

    /**
     * @var XsBroker $brokerModel
     */
    private $brokerModel = XsBroker::class;

    /**
     * @var XsBrokerUser $brokerUserModel
     */
    private $brokerUserModel = XsBrokerUser::class;

    /**
     * @var XsBigarea $bigAreaModel
     */
    private $bigAreaModel = XsBigarea::class;

    /**
     * @var XsUserBigarea $userBigAreaModel
     */
    private $userBigAreaModel = XsUserBigarea::class;

    /**
     * @var XsstChatroomDefaultCover $coverModel
     */
    private $coverModel = XsstChatroomDefaultCover::class;

    /**
     * @var XsstChatroomAdminLog $logModel
     */
    private $logModel = XsstChatroomAdminLog::class;

    /**
     * @var XsBmsVideoLiveStopLog $closeLogModel
     */
    private $closeLogModel = XsBmsVideoLiveStopLog::class;

    /**
     * @var XsBanRoomLog $forbiddenModel
     */
    private $forbiddenModel = XsBanRoomLog::class;

    /**
     * @var CmsUser $adminModel
     */
    private $adminModel = CmsUser::class;

    public function getListAndTotal(array $params): array
    {
        $conditions = $this->getConditions($params);
        $list = $this->model::getListAndTotal($conditions, '*', 'rid desc', $params['page'] ?? 1, $params['limit'] ?? 15);
        if (empty($list['data'])) {
            return $list;
        }

        $uidList = Helper::arrayFilter($list['data'], 'uid');
        $brokerUserList = $this->brokerUserModel::getBrokerUserBatch($uidList);
        $brokerList = $this->brokerModel::getBrokerBatch(Helper::arrayFilter($brokerUserList, 'bid'));
        $roomConfigList = $this->configModel::getListByRidBatch(Helper::arrayFilter($list['data'], 'rid'));
        $userBigAreaList = $this->userBigAreaModel::getUserBigAreaBatch($uidList);
        $roomOnlineList = ChatroomRedis::getOnlineCount();

        foreach ($list['data'] as &$item) {
            $item['id'] = $item['rid'];
            $item['origin_icon'] = $item['icon'];
            $item['icon'] = Helper::getOnlineUrl($item['icon']);
            $item['sicon'] = $item['bicon'];
            $item['bicon'] = Helper::getOnlineUrl($item['bicon']);
            $item['online_num'] = $roomOnlineList[$item['rid']] ?? 0;
            $item['bid'] = $brokerUserList[$item['uid']]['bid'] ?? 0;
            $item['bname'] = $brokerList[$item['bid']]['bname'] ?? '';
            $item['big_area'] = $userBigAreaList[$item['uid']]['bigarea_id'] ?? 0;
            $item['weight'] = $this->setWeight($item['weight']);
            $item['nine'] = $item['nine'] > 0 ? $this->model::NINE_YES : $this->model::NINE_NO;

            $this->setConfigItem($roomConfigList[$item['rid']] ?? [], $item);

        }

        return $list;
    }


    /**
     * 设置背景图
     * @param array $params
     * @return array
     */
    public function backgroundModify(array $params): array
    {
        $type = trim($params['type'] ?? '');

        if (empty($type)) {
            throw new ApiException(ApiException::MSG_ERROR, '背景类型不能为空');
        }

        $background = $this->backgroundModel::getInfoByType($type);

        if (empty($background)) {
            throw new ApiException(ApiException::MSG_ERROR, '背景类型不存在');
        }

        $updateConditions = [
            ['deleted', '=', $this->model::DELETED_NORMAL],
            ['language', 'IN', Helper::getSystemUserLanguage()]
        ];

        $chatroomList = $this->model::getListByWhere($updateConditions, 'rid');

        if (empty($chatroomList)) {
            throw new ApiException(ApiException::MSG_ERROR, '没有可修改的聊天室');
        }

        $update = [
            'background' => $type
        ];

        list($res, $msg) = $this->model::updateByWhere($updateConditions, $update);

        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, '设置背景图失败，原因：' . $msg);
        }

        return ['rid' => Helper::arrayFilter($chatroomList, 'rid'), 'after_json' => $update];
    }

    /**
     * 聊天室封面修改
     * @param array $params
     * @return array
     * @throws ApiException
     */
    public function coverModify(array $params): array
    {
        $rid = intval($params['rid'] ?? 0);

        if (empty($rid)) {
            throw new ApiException(ApiException::MSG_ERROR, '聊天室id不能空');
        }

        list($res, $msg) = (new PsService())->roomCoverChange($rid);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        $logData = [
            'icon' => $this->model::DEFAULT_ICON
        ];

        $this->logModel::addRecord($rid, $this->logModel::AC_EDIT, $logData);

        return ['rid' => $rid, 'after_json' => $logData];
    }

    /**
     * 聊天室名字修改
     * @param array $params
     * @return array
     * @throws ApiException
     */
    public function prefixModify(array $params): array
    {
        $rid = intval($params['rid'] ?? 0);
        $prefix = trim($params['prefix'] ?? '');

        if (empty($rid) || empty($prefix)) {
            throw new ApiException(ApiException::MSG_ERROR, '聊天室id或聊天室名字不能为空');
        }

        $prefix = strtoupper($prefix);
        if (!preg_match("/^[A-Z][0-9A-Z\-]{1,3}$/", $prefix)) {
            throw new ApiException(ApiException::MSG_ERROR, '房间名称不符合要求');
        }

        $chatroom = $this->model::useMaster()::findOne($rid);

        if (empty($chatroom) || $chatroom['property'] != $this->model::PROPERTY_BUSINESS) {
            throw new ApiException(ApiException::MSG_ERROR, '聊天室不存在或者不是商业厅');
        }

        $data = [
            'prefix' => $prefix
        ];

        list($res, $msg) = $this->model::edit($rid, $data);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, '修改失败，原因：' . $msg);
        }

        return ['rid' => $rid, 'after_json' => $data];
    }

    /**
     * 关闭房间
     * @param array $params
     * @return array
     * @throws ApiException
     */
    public function close(array $params): array
    {
        $rid = intval($params['rid'] ?? 0);
        $reason = intval($params['reason'] ?? 0);
        $remarks = trim($params['remarks'] ?? '');

        if (empty($rid) || empty($reason)) {
            throw new ApiException(ApiException::MSG_ERROR, '聊天室id或关闭原因不能为空');
        }

        $data = [
            'rid'    => $rid,
            'reason' => $this->model::CLOSE_KEY_PREFIX . $reason,
            'uid'    => $params['admin_uid']
        ];

        list($res, $msg) = (new PsService())->closeRoom($data);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        $logData = [
            'id'      => $rid,
            'rid'     => $rid,
            'reason'  => $reason,
            'remarks' => $remarks,
        ];

        $this->closeLogModel::addStopLog($logData, $this->closeLogModel::TYPE_ROOM);

        return ['rid' => $rid, 'after_json' => $data];
    }

    /**
     * 配置权重
     * @param int $weight
     * @return string
     */
    private function setWeight(int $weight): string
    {
        if ($weight == 100 || $weight == 0) {
            return '不变';
        }
        if ($weight > 100) {
            return '0';
        }

        if ($weight > 0) {
            return (100 - $weight) . '%';
        }
        if ($weight < 0) {
            return (100 + (0 - $weight)) . '%';
        }

        return (string)$weight;
    }

    /**
     * 设置玩法相关字段
     * @param array $config
     * @param array $item
     * @return void
     */
    private function setConfigItem(array $config, array &$item): void
    {
        // 初始化默认值
        $defaultValues = [
            'switching_time'           => '0',
            'switching_start_time'     => '',
            'switching_end_time'       => '',
            'moban_time'               => '不开放',
            'theone_time'              => '0',
            'theone_start_time'        => '',
            'theone_end_time'          => '',
            'theone_moban_time'        => '不开放',
            'vocal_concert'            => '0',
            'vocal_concert_start_time' => '',
            'vocal_concert_week'       => '',
            'vocal_concert_end_time'   => '',
            'vocal_concert_moban_time' => '不开放'
        ];

        // 合并默认值和传入的 item 数组
        $item = array_merge($defaultValues, $item);

        // 检查 config 是否为空
        if (empty($config)) {
            return;
        }

        foreach ($config as $conf) {
            if (!isset($conf['play_type'])) {
                continue;
            }

            // 获取 start 和 end 时间
            $startTime = isset($item['start']) ? Helper::now($item['start']) : '';
            $endTime = isset($item['end']) ? Helper::now($item['end']) : '';

            switch ($conf['play_type']) {
                case $this->configModel::PLAY_TYPE_AUCTION:
                    $item['switching_time'] = '1';
                    $item['switching_start_time'] = $startTime;
                    $item['switching_end_time'] = $endTime;
                    $item['moban_time'] = "$startTime-$endTime";
                    break;

                case $this->configModel::PLAY_TYPE_THEONE:
                    $item['theone_time'] = '1';
                    $item['theone_start_time'] = $startTime;
                    $item['theone_end_time'] = $endTime;
                    $item['theone_moban_time'] = "$startTime-$endTime";
                    break;

                case $this->configModel::PLAY_TYPE_CONCERT:
                    $item['vocal_concert'] = '1';
                    $item['vocal_concert_week'] = $item['week'] != '' ? explode(',', $item['week']) : '';
                    $item['vocal_concert_start_time'] = $startTime;
                    $item['vocal_concert_end_time'] = $endTime;
                    $item['vocal_concert_moban_time'] = "$startTime-$endTime";
                    break;
            }
        }
    }

    private function getConditions(array $params): array
    {
        $conditions = [
            ['language', 'IN', Helper::getSystemUserLanguage($params['admin_uid'])]
        ];

        $rid = intval($params['rid'] ?? 0);
        $uid = intval($params['uid'] ?? 0);
        $bid = intval($params['bid'] ?? 0);
        $name = trim($params['name'] ?? '');
        $deleted = intval($params['deleted'] ?? -2);

        $rid && $conditions[] = ['rid', '=', $rid];
        $uid && $conditions[] = ['uid', '=', $uid];
        $name && $conditions[] = ['name', 'LIKE', "$name"];

        if ($bid) {
            $brokerUsers = $this->brokerUserModel::getBrokerUsers($bid);
            $brokerUsers && $conditions[] = ['uid', 'IN', $brokerUsers];
        }

        switch ($deleted) {
            case $this->model::DELETED_NORMAL:
            case $this->model::DELETED_FORBIDDEN:
                $conditions[] = ['deleted', '=', $deleted];
                break;
            case $this->model::DELETED_CLOSE:
                $conditions[] = ['deleted', 'IN', $this->model::DELETED_WAIT_ADD, $this->model::DELETED_CLOSE];
                break;
        }

        return $conditions;
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

    public function getPropertyMap(): array
    {
        return StatusService::formatMap($this->model::$propertyAllMap);
    }

    public function getRoomFactoryTypeMap(): array
    {
        return StatusService::formatMap($this->factoryModel::getOptions());
    }

    public function getFixedTagIdMap(): array
    {

        return StatusService::formatMap($this->tagModel::getOptions());
    }

    public function getSettlementChannelMap(): array
    {
        return StatusService::formatMap($this->channelModel::getOptions());
    }

    public function getModeMap(): array
    {
        return StatusService::formatMap($this->model::$modeMap);
    }

    public function getNineMap(): array
    {
        return StatusService::formatMap($this->model::$nineMap);
    }

    public function getDeletedMap(): array
    {
        return StatusService::formatMap($this->model::$deletedMap);
    }

    public function getAreaMap(): array
    {
        return StatusService::formatMap($this->model::getAreaMap());
    }

    public function getRoomBackgroundTypeMap(): array
    {
        return StatusService::formatMap($this->backgroundModel::getBackgroundTypeMap());
    }

    public function getSexMap(): array
    {
        return StatusService::formatMap($this->model::$sexMap);
    }

    public function getIconMap(): array
    {
        return StatusService::formatMap($this->coverModel::getOptions());
    }

    public function getWeekMap(): array
    {
        return StatusService::formatMap($this->model::getWeekMap());
    }

    public function getTimeTypeMap(): array
    {
        return StatusService::formatMap($this->model::$timeTypeMap);
    }

    public function getReasonMap(): array
    {
        return StatusService::formatMap($this->forbiddenModel::$reasonMap);
    }

    public function getModel(): XsChatroom
    {
        return $this->model;
    }
}