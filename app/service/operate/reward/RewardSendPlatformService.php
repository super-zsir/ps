<?php

namespace Imee\Service\Operate\Reward;

use Imee\Comp\Operate\Auth\Models\Cms\CmsUser;
use Imee\Exception\ApiException;
use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xs\XsCertificationSign;
use Imee\Models\Xs\XsChatroomBackgroundMall;
use Imee\Models\Xs\XsCommodity;
use Imee\Models\Xs\XsCommodityAdmin;
use Imee\Models\Xs\XsCoupon;
use Imee\Models\Xs\XsCouponScene;
use Imee\Models\Xs\XsCustomizePrettyStyle;
use Imee\Models\Xs\XsEmoticons;
use Imee\Models\Xs\XsEmoticonsGroup;
use Imee\Models\Xs\XsGift;
use Imee\Models\Xs\XsMedalResource;
use Imee\Models\Xs\XsRoomSkin;
use Imee\Models\Xs\XsRoomTopCard;
use Imee\Models\Xs\XsUserCustomizePretty;
use Imee\Models\Xsst\BmsVipSendDetail;
use Imee\Models\Xs\XsstCouponIssued;
use Imee\Models\Xsst\XsstRewardTemplate;
use Imee\Models\Xsst\XsstRewardWhitelist;
use Imee\Service\Helper;
use Imee\Service\Operate\Background\Custombackground\CustomBgcCardSendService;
use Imee\Service\Operate\Coupon\GameCouponIssuedService;
use Imee\Service\Operate\Emoticons\EmoticonsRewardService;
use Imee\Service\Rpc\PsService;
use Imee\Service\StatusService;

/**
 * 奖励发放平台
 */
class RewardSendPlatformService
{
    /**
     * 获取列表
     * @param array $params
     * @return array
     */
    public function getList(array $params): array
    {
        $list = XsstRewardTemplate::getListAndTotal([], '*', 'id desc', $params['page'] ?? 1, $params['limit'] ?? 15);

        if (empty($list)) {
            return $list;
        }

        $bigAreaList = XsBigarea::getAllNewBigArea();
        $adminList = CmsUser::getAdminUserBatch(Helper::arrayFilter($list['data'], 'admin_id'));
        foreach ($list['data'] as &$item) {
            $item['reward_list_object'] = [
                'title'    => '奖励内容',
                'value'    => '奖励内容',
                'type'     => 'manMadeModal',
                'modal_id' => 'table_modal',
                'params'   => [
                    'guid' => 'rewardsendplatforminfo',
                    'id'   => $item['id']
                ],
            ];
            $item['reward_list'] = $this->formatRewardList($item['reward_list']);
            $item['status'] = (string)$item['status'];
            $item['limit_big_area'] = (string)$item['limit_big_area'];
            $item['limit_big_area_text'] = $bigAreaList[$item['limit_big_area']];
            $item['limit_object_text'] = XsstRewardTemplate::$limitObjectMap[$item['limit_object']];
            $item['status_text'] = XsstRewardTemplate::$statusMap[$item['status']];
            $item['admin_name'] = $adminList[$item['admin_id']]['user_name'] ?? '';
            $item['dateline'] = Helper::now($item['dateline']);
        }

        return $list;
    }

    public function formatRewardList(string $rewardList): array
    {
        $rewardList = @json_decode($rewardList, true);

        foreach ($rewardList as &$reward) {
            $reward['type'] = (string)$reward['type'];
            foreach (XsstRewardTemplate::$rewardItemFieldMap as $keyField => $valueField) {
                !isset($reward[$keyField]) && $reward[$keyField] = $reward[$valueField] ?? 0;
            }
            $reward = array_map('strval', $reward);
        }
        return $rewardList;

    }

    /**
     * 创建
     * @param array $params
     * @return array
     * @throws ApiException
     */
    public function create(array $params): array
    {
        $data = $this->validate($params);
        list($res, $msg) = XsstRewardTemplate::add($data);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, '添加失败，' . $msg);
        }

        return ['id' => $msg, 'after_json' => $data];
    }

    /**
     * 验证创建数据
     * @param array $params
     * @return array
     */
    private function validate(array $params): array
    {
        $name = trim($params['name']);
        $rewardList = $params['reward_list'];
        $limitBigArea = intval($params['limit_big_area']);
        $limitObject = intval($params['limit_object']);
        $maxSendNum = intval($params['max_send_num']);
        $remark = trim($params['remark'] ?? '');
        $adminId = $params['admin_id'];
        $now = time();

        if (Helper::checkAdminBigArea($adminId, $limitBigArea)) {
            throw new ApiException(ApiException::MSG_ERROR, '没有权限操作该大区，请检查后重试');
        }

        $bigArea = XsBigarea::findOne($limitBigArea);
        $rewardListData = [];
        foreach ($rewardList as $key => $rewardItem) {
            $index = $key + 1;
            $type = intval($rewardItem['type']);
            $sendNum = intval($rewardItem['send_num'] ?? 1);
            $this->validateRewardItem($index, $type, $rewardItem, $bigArea);
            $rewardListData[] = $this->initRewardItemData($index, $type, $sendNum, $rewardItem);
        }

        return [
            'name'           => $name,
            'limit_big_area' => $limitBigArea,
            'limit_object'   => $limitObject,
            'max_send_num'   => $maxSendNum,
            'remark'         => $remark,
            'admin_id'       => $adminId,
            'dateline'       => $now,
            'reward_list'    => json_encode($rewardListData),
            'status'         => XsstRewardTemplate::STATUS_EFFECTIVE
        ];
    }

    private function validateRewardItem(int $index, int $type, array $rewardItem, array $bigArea): void
    {
        try {
            $action = XsstRewardTemplate::$validRewardActionMap[$type] ?? '';
            $type = XsstRewardTemplate::$rewardTypeMap[$rewardItem['type']];
            method_exists($this, $action) && call_user_func([$this, $action], ['rewardItem' => $rewardItem, 'bigArea' => $bigArea]);
        } catch (ApiException $e) {
            throw new ApiException(ApiException::MSG_ERROR, sprintf('第%d行，%s奖励类型下，id:%d，%s，请检查后重试', $index, $type, $rewardItem['id'] ?? $rewardItem['vip_level'] ?? 0, $e->getMsg()));
        }
    }

    public function validCommodityReward(array $params): void
    {
        $bigArea = $params['bigArea'] ?? [];
        $rewardItem = $params['rewardItem'];
        $id = $rewardItem['id'];

        $commodity = XsCommodityAdmin::getInfo($id);
        if (empty($commodity)) {
            throw new ApiException(ApiException::MSG_ERROR, 'id错误');
        }

        if ($commodity['only_newpay'] == 1) {
            throw new ApiException(ApiException::MSG_ERROR, '新充值奖励的物品禁止发放');
        }

        // 幸运礼物禁止发放
        if ($commodity['type'] == 'gift' && $commodity['ext_id']) {
            $gift = XsGift::hasLuckyGift($commodity['ext_id']);
            if ($gift) {
                throw new ApiException(ApiException::MSG_ERROR, '幸运礼物禁止发放');
            }
        }
        if ($bigArea) {
            $codeBigArea = $bigArea['name'] == 'cn' ? ['zh_tw', 'zh_cn'] : [$bigArea['name']];
            $excludes = explode(',', $commodity['excludes']);
            $intersectBigArea = array_intersect($codeBigArea, $excludes);
            if ($intersectBigArea) {
                throw new ApiException(ApiException::MSG_ERROR, '排除地区与限制发放大区冲突');
            }
        }
    }

    private function validVipReward(array $params)
    {
        $rewardItem = $params['rewardItem'];
        $giveType = $rewardItem['give_type'];
        $sendNum = intval($rewardItem['send_num'] ?? 0);

        if ($giveType != 0 && empty($sendNum)) {
            throw new ApiException(ApiException::MSG_ERROR, '发放数量不能为空');
        }
    }

    private function validPrettyReward(array $params)
    {
        $rewardItem = $params['rewardItem'];
        $id = $rewardItem['id'];

        $pretty = XsCustomizePrettyStyle::findOne($id);
        if (empty($pretty)) {
            throw new ApiException(ApiException::MSG_ERROR, 'id错误');
        }
    }

    private function validMedalReward(array $params)
    {
        $bigArea = $params['bigArea'];
        $rewardItem = $params['rewardItem'];
        $id = $rewardItem['id'];

        $medal = XsMedalResource::getInfo($id);
        if (empty($medal)) {
            throw new ApiException(ApiException::MSG_ERROR, 'id错误');
        }

        if ($bigArea['name'] != $medal['big_area']) {
            throw new ApiException(ApiException::MSG_ERROR, '大区与限制发放大区必须一致');
        }
    }

    private function validCertificationSignReward(array $params)
    {
        $rewardItem = $params['rewardItem'];
        $id = $rewardItem['id'];

        $medal = XsCertificationSign::findOne($id);
        if (empty($medal)) {
            throw new ApiException(ApiException::MSG_ERROR, 'id错误');
        }
    }

    private function validRoomBackgroundReward(array $params)
    {
        $bigArea = $params['bigArea'];
        $rewardItem = $params['rewardItem'];
        $id = $rewardItem['id'];

        $roomBackGround = XsChatroomBackgroundMall::getInfo($id);
        if (empty($roomBackGround)) {
            throw new ApiException(ApiException::MSG_ERROR, 'id错误');
        }

        if ($bigArea['name'] != $roomBackGround['big_area']) {
            throw new ApiException(ApiException::MSG_ERROR, '大区与限制发放大区必须一致');
        }
    }

    private function validRoomTopCardReward(array $params)
    {
        $rewardItem = $params['rewardItem'];
        $id = $rewardItem['id'];

        $roomTopCard = XsRoomTopCard::getInfo($id);
        if (empty($roomTopCard)) {
            throw new ApiException(ApiException::MSG_ERROR, 'id错误');
        }
    }

    private function validRoomSkinReward(array $params)
    {
        $rewardItem = $params['rewardItem'];
        $id = $rewardItem['id'];

        $roomSkin = XsRoomSkin::getInfo($id);
        if (empty($roomSkin)) {
            throw new ApiException(ApiException::MSG_ERROR, 'id错误');
        }
    }

    private function validGameCouponReward(array $params)
    {
        $bigArea = $params['bigArea'];
        $rewardItem = $params['rewardItem'];
        $id = $rewardItem['id'];
        $bigAreaID = $rewardItem['big_area'];

        list($amountFlg, $amount) = (new GameCouponIssuedService())->getCouponAmount($id);
        if (!$amountFlg) {
            throw new ApiException(ApiException::MSG_ERROR, $amount);
        }

        if ($bigArea['id'] != $bigAreaID) {
            throw new ApiException(ApiException::MSG_ERROR, '大区与限制发放大区必须一致');
        }
    }

    private function validEmoticonsReward(array $params)
    {
        $bigArea = $params['bigArea'];
        $rewardItem = $params['rewardItem'];
        $id = $rewardItem['id'];
        $emoticon = XsEmoticons::findOne($id);
        if (!$emoticon
            || $emoticon['status'] != XsEmoticons::LISTED_STATUS
            || !in_array($emoticon['identity'], [XsEmoticons::EMOTICONS_IDENTITY_SELL, XsEmoticons::EMOTICONS_IDENTITY_ACTIVE])
        ) {
            throw new ApiException(ApiException::MSG_ERROR, '配置ID数据有更新，请重新选择');
        }

        if ($bigArea['id'] != $emoticon['bigarea_id']) {
            throw new ApiException(ApiException::MSG_ERROR, '大区与限制发放大区必须一致');
        }
    }

    /**
     * 初始化奖励数据
     * @param int $index
     * @param int $type
     * @param int $sendNum
     * @param array $reward
     * @return array
     */
    private function initRewardItemData(int $index, int $type, int $sendNum, array $reward): array
    {
        $fieldArr = XsstRewardTemplate::$initRewardItemDataMap[$type];
        $data = [];
        foreach ($fieldArr as $field) {
            $value = $field == 'content' ? $reward[$field] : intval($reward[$field]);
            isset($reward[$field]) && $data[$this->getFieldName($field)] = $value;
        }

        // 特殊处理下vip类型下的send_num，直接生效下重置为1
        if ($type == XsstRewardTemplate::REWARD_TYPE_VIP && $reward['give_type'] == 0) {
            $sendNum = 1;
        }
        return array_merge(['index' => $index, 'type' => $type, 'send_num' => $sendNum], $data);
    }

    /**
     * 处理特殊字段转换
     * @param string $field
     * @return string
     */
    private function getFieldName(string $field): string
    {
        return XsstRewardTemplate::$rewardItemFieldMap[$field] ?? $field;
    }

    /**
     * 修改奖励模板
     * @param array $params
     * @return array
     * @throws ApiException
     */
    public function modify(array $params): array
    {
        $id = intval($params['id']);
        $name = trim($params['name']);
        $status = intval($params['status']);
        $remark = trim($params['remark'] ?? '');
        $adminId = $params['admin_id'];

        $template = XsstRewardTemplate::findOne($id);
        if (empty($template)) {
            throw new ApiException(ApiException::MSG_ERROR, '该模板不存在');
        }

        $update = [
            'name'     => $name,
            'status'   => $status,
            'remark'   => $remark,
            'admin_id' => $adminId,
            'dateline' => time(),
        ];

        list($res, $msg) = XsstRewardTemplate::edit($id, $update);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, '更新失败：' . $msg);
        }

        $beforeJson = [
            'name'   => $template['name'],
            'status' => $template['status'],
            'remark' => $template['remark'],
        ];

        return ['id' => $id, 'after_json' => $update, 'before_json' => $beforeJson];
    }

    public function info(array $params): array
    {
        $id = intval($params['id'] ?? 0);
        if (empty($id)) {
            throw new ApiException(ApiException::MSG_ERROR, 'Param error');
        }

        $template = XsstRewardTemplate::findOne($id);
        if (empty($template)) {
            throw new ApiException(ApiException::MSG_ERROR, 'Template not exists');
        }

        $template['limit_object'] = (string)$template['limit_object'];
        $template['limit_big_area'] = (string)$template['limit_big_area'];
        $template['reward_list'] = $this->formatRewardList($template['reward_list']);

        return $template;
    }

    /**
     * 获取奖励模版详情
     * @param array $params
     * @return array
     * @throws ApiException
     */
    public function getRewardList(array $params): array
    {
        $id = $params['id'] ?? 0;
        if (empty($id)) {
            throw new ApiException(ApiException::MSG_ERROR, '模版id必传');
        }

        $template = XsstRewardTemplate::findOne($id);
        if (empty($template)) {
            return $template;
        }

        $rewardList = @json_decode($template['reward_list'], true) ?? [];
        $list = [];
        $bigAreaList = XsBigarea::getAllNewBigArea();
        $couponSceneList = (new PsService())->getGameCouponScene();
        foreach ($rewardList as $reward) {
            $tmp = [
                'index'      => $reward['index'],
                'type'       => XsstRewardTemplate::$rewardTypeMap[$reward['type']],
                'sub_type'   => '',
                'id'         => $reward['id'] ?? '',
                'name'       => '',
                'valid_days' => $reward['valid_days'] ?? '',
                'send_num'   => $reward['send_num'],
                'give_type'  => '',
                'extra'      => '',
            ];
            switch ($reward['type']) {
                case XsstRewardTemplate::REWARD_TYPE_COMMODITY:
                    $commodity = XsCommodity::findOne($reward['id']);
                    if ($commodity) {
                        $tmp['sub_type'] = XsCommodityAdmin::$typeMap[$commodity['type']] ?? '';
                        $tmp['name'] = $commodity['name'] ?: $commodity['name_en'];
                        $tmp['give_type'] = $commodity['can_give'] ? '是' : '否';
                        $tmp['valid_days'] = $commodity['period'] ?: '';
                    }
                    break;
                case XsstRewardTemplate::REWARD_TYPE_VIP:
                    $tmp['sub_type'] = BmsVipSendDetail::$displayVipLevel[$reward['id']];
                    $tmp['id'] = '';
                    $tmp['name'] = $tmp['sub_type'];
                    $tmp['give_type'] = BmsVipSendDetail::$giveTypeMaps[$reward['give_type']] ?? '';
                    break;
                case XsstRewardTemplate::REWARD_TYPE_OPTIONAL_PRETTY:
                    $pretty = XsCustomizePrettyStyle::findOne($reward['id']);
                    if ($pretty) {
                        $tmp['name'] = $pretty['name'];
                    }
                    $tmp['sub_type'] = $tmp['type'];
                    $tmp['give_type'] = $reward['give_type'] ? '可转赠' : '不可转赠';
                    $tmp['extra'] = '资格使用有效天数：' . $reward['use_valid_days'];
                    break;
                case XsstRewardTemplate::REWARD_TYPE_MEDAL:
                    $medal = XsMedalResource::findOne($reward['id']);
                    if ($medal) {
                        $desc = @json_decode($medal['description_zh_tw'], true) ?? [];
                        $tmp['name'] = $desc['name'] ?? '';
                        $tmp['sub_type'] = XsMedalResource::$typeMap[$medal['type']] ?? '';
                    }
                    $tmp['give_type'] = '不可转赠';
                    break;
                case XsstRewardTemplate::REWARD_TYPE_CERTIFICATION_SIGN:
                    $certificationSign = XsCertificationSign::findOne($reward['id']);
                    if ($certificationSign) {
                        $tmp['name'] = $certificationSign['name'];
                    }
                    $tmp['extra'] = '认证文案：' . $reward['content'];
                    $tmp['sub_type'] = $tmp['type'];
                    $tmp['give_type'] = '不可转赠';
                    break;
                case XsstRewardTemplate::REWARD_TYPE_ROOM_BACKGROUND:
                    $bgc = XsChatroomBackgroundMall::findOneByWhere([['bg_id', '=', $reward['id']]]);
                    if ($bgc) {
                        $tmp['name'] = $bgc['name'];
                    }
                    $tmp['sub_type'] = $tmp['type'];
                    $tmp['give_type'] = '不可转赠';
                    break;
                case XsstRewardTemplate::REWARD_TYPE_ROOM_CUSTOM_BACKGROUND_CARD:
                    $tmp['give_type'] = $reward['give_type'] ? '是' : '否';
                    $tmp['sub_type'] = $tmp['name'] = $tmp['type'];
                    break;
                case XsstRewardTemplate::REWARD_TYPE_ROOM_TOP_CARD:
                    $topCard = XsRoomTopCard::findOne($reward['id']);
                    if ($topCard) {
                        $nameJson = @json_decode($topCard['name_json'], true) ?? [];
                        $tmp['name'] = $nameJson['cn'] ?? '';
                    }
                    $tmp['sub_type'] = $tmp['type'];
                    $tmp['give_type'] = '可转赠';
                    break;
                case XsstRewardTemplate::REWARD_TYPE_ROOM_SKIN:
                    $skin = XsRoomSkin::findOne($reward['id']);
                    if ($skin) {
                        $tmp['sub_type'] = XsRoomSkin::$typeMap[$skin['type']] ?? '';
                        $tmp['name'] = $skin['name'];
                    }
                    $tmp['give_type'] = '不可转赠';
                    break;
                case XsstRewardTemplate::REWARD_TYPE_GAME_COUPON:
                    $coupon = XsCoupon::findOne($reward['id']);
                    if ($coupon) {
                        $nameJson = @json_decode($coupon['name_json'], true) ?? [];
                        $tmp['name'] = $nameJson['cn'] ?? '';
                        $scene = XsCouponScene::getListByWhere([
                            ['coupon_id', '=', $coupon['id']],
                        ], 'scene');
                        if ($scene) {
                            $tmp['sub_type'] = array_map(function ($v) use ($couponSceneList) {
                                return $couponSceneList[$v] ?? '';
                            }, array_column($scene, 'scene'));
                            $tmp['sub_type'] = implode(',', $tmp['sub_type']);
                        }
                        $tmp['valid_days'] = XsstCouponIssued::$expire[$reward['expire']] ?? '';
                        $tmp['extra'] = '优惠券大区：' . ($bigAreaList[$reward['big_area']] ?? '');
                        $tmp['give_type'] = '不可转赠';
                    }
                    break;
                case XsstRewardTemplate::REWARD_TYPE_EMOTICONS:
                    $emoticons = XsEmoticons::findOne($reward['id']);
                    if ($emoticons) {
                        $group = XsEmoticonsGroup::findOne($emoticons['group_id']);
                        $tmp['name'] = $group['name'] ?? '';
                    }
                    $tmp['sub_type'] = $tmp['type'];
                    $tmp['give_type'] = '不可转赠';
                    break;
            }
            $list[] = $tmp;
        }

        return $list;
    }

    /**
     * 获取状态映射
     * @return array
     */
    public function getStatusMap(): array
    {
        return StatusService::formatMap(XsstRewardTemplate::$statusMap, 'label,value');
    }

    /**
     * 获取限制对象映射
     * @return array
     */
    public function getLimitObjectMap(): array
    {
        return StatusService::formatMap(XsstRewardTemplate::$limitObjectMap, 'label,value');
    }

    /**
     * 获取奖励对象
     * @return array
     */
    public function getRewardTypeMap(): array
    {
        return StatusService::formatMap(XsstRewardTemplate::$rewardTypeMap, 'label,value');
    }

    /**
     * 获取奖励对应id
     * @param int $type
     * @return array
     */
    public function getRewardMap(int $type): array
    {
        $service = new StatusService();
        $data = [];
        switch ($type) {
            case XsstRewardTemplate::REWARD_TYPE_COMMODITY:
                $data = $service->getCommodityMap(null, 'label,value');
                break;
            case XsstRewardTemplate::REWARD_TYPE_OPTIONAL_PRETTY:
                $data = $service->getPrettyCardMap(null, 'label,value');
                break;
            case XsstRewardTemplate::REWARD_TYPE_MEDAL:
                $data = $service->getMedalMap(null, 'label,value');
                break;
            case XsstRewardTemplate::REWARD_TYPE_CERTIFICATION_SIGN:
                $data = $service->getCertificationMap(null, 'label,value');
                break;
            case XsstRewardTemplate::REWARD_TYPE_ROOM_BACKGROUND:
                $data = $service->getBackgroundMap(null, 'label,value');
                break;
            case XsstRewardTemplate::REWARD_TYPE_ROOM_TOP_CARD:
                $data = $service->getRoomTopCardMap(null, 'label,value');
                break;
            case XsstRewardTemplate::REWARD_TYPE_ROOM_SKIN:
                $data = $service->getRoomSkinMap(null, 'label,value');
                break;
            case XsstRewardTemplate::REWARD_TYPE_GAME_COUPON:
                $data = GameCouponIssuedService::getGameCouponAllList();
                break;
            case XsstRewardTemplate::REWARD_TYPE_EMOTICONS:
                $data = (new EmoticonsRewardService())->getEmoticonsMap();
                break;
        }
        return $data;
    }

    /**
     * 获取优惠券有效期
     * @return array
     */
    public function getExpireMap(): array
    {
        return StatusService::formatMap(XsstCouponIssued::$expire, 'label,value');
    }

    /**
     * 获取是否可赠送
     * @param int $type
     * @return array
     */
    public function getGiveTypeMap(int $type): array
    {
        $giveType = [];
        switch ($type) {
            case XsstRewardTemplate::REWARD_TYPE_VIP:
                $giveType = BmsVipSendDetail::$giveTypeMaps;
                break;
            case XsstRewardTemplate::REWARD_TYPE_OPTIONAL_PRETTY:
                $giveType = XsUserCustomizePretty::$giveTypeMaps;
                break;
            case XsstRewardTemplate::REWARD_TYPE_ROOM_CUSTOM_BACKGROUND_CARD:
                $giveType = CustomBgcCardSendService::$giveTypeMaps;
                break;
        }

        return StatusService::formatMap($giveType, 'label,value');
    }

    /**
     * 获取认证标识文案
     * @param int $id
     * @return string
     */
    public function getContentValue(int $id): string
    {
        $info = XsCertificationSign::findOne($id);
        return $info ? $info['default_content'] : '';
    }

    /**
     * 获取发放白名单列表
     * @param array $params
     * @return array
     */
    public function getWhitelistList(array $params): array
    {
        $list = XsstRewardWhitelist::getListAndTotal([
            ['type', '=', $params['type']],
        ], '*', 'id desc', $params['page'] ?? 1, $params['limit'] ?? 15);

        if (empty($list['data'])) {
            return $list;
        }

        $adminId = Helper::arrayFilter($list['data'], 'user_id');
        $createId = Helper::arrayFilter($list['data'], 'admin_id');
        $adminAllId = array_values(array_filter(array_unique(array_merge($adminId, $createId))));
        $adminList = CmsUser::getAdminUserBatch($adminAllId, ['user_id', 'user_name', 'job_num']);

        foreach ($list['data'] as &$item) {
            $item['user_name'] = $adminList[$item['user_id']]['user_name'] ?? '';
            $item['job_num'] = $adminList[$item['user_id']]['job_num'] ?? '';
            $item['admin_name'] = $adminList[$item['admin_id']]['user_name'] ?? '';
            $item['dateline'] = Helper::now($item['dateline']);
        }

        return $list;
    }

    /**
     * 发奖白名单添加
     * @param array $params
     * @return array
     * @throws ApiException
     */
    public function whitelistCreate(array $params): array
    {
        $type = intval($params['type'] ?? 0);
        $adminId = intval($params['user_id'] ?? 0);

        if (empty($adminId) || empty($type)) {
            throw new ApiException(ApiException::MSG_ERROR, '后台用户ID、Type不能为空');
        }

        $user = CmsUser::findOne($adminId);
        if (empty($user)) {
            throw new ApiException(ApiException::MSG_ERROR, '后台用户不存在');
        }

        $whitelist = XsstRewardWhitelist::findOneByWhere([
            ['user_id', '=', $adminId],
            ['type', '=', $type]
        ]);

        if ($whitelist) {
            throw new ApiException(ApiException::MSG_ERROR, '用户该类型白名单已经存在');
        }

        $data = [
            'user_id'  => $adminId,
            'type'     => $params['type'],
            'admin_id' => $params['admin_id'],
            'dateline' => time(),
        ];

        list($res, $msg) = XsstRewardWhitelist::add($data);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }


        return ['id' => $msg, 'after_json' => $data];
    }

    /**
     * 发奖白名单删除
     * @param array $params
     * @return array
     * @throws ApiException
     */
    public function whitelistDelete(array $params): array
    {
        $id = intval($params['id'] ?? 0);

        $whitelist = XsstRewardWhitelist::findOne($id);
        if (empty($whitelist)) {
            throw new ApiException(ApiException::MSG_ERROR, '白名单不存在');
        }

        $res = XsstRewardWhitelist::deleteById($id);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, '删除失败');
        }

        return [
            'id'          => $id,
            'before_json' => [
                'admin_id' => $whitelist['admin_id'],
                'dateline' => $whitelist['dateline']
            ]
        ];
    }

    public function getOptions(): array
    {
        $service = new StatusService();
        $limitBigArea = $service->getFamilyBigArea(null, 'label,value');
        $limitObject = $this->getLimitObjectMap();
        $rewardType = $this->getRewardTypeMap();

        $rewardId = [];
        foreach (XsstRewardTemplate::$rewardTypeMap as $type => $rewardName) {
            $rewardId[$type] = $this->getRewardMap($type);
        }
        $giveType = [];
        foreach (XsstRewardTemplate::$rewardTypeMap as $type => $rewardName) {
            $giveType[$type] = $this->getGiveTypeMap($type);
        }
        $vipLevel = $service->getVipMap(null, 'label,value');
        $vipDays = $service->getVipDaysMap(null, 'label,value');
        $expire = $this->getExpireMap();
        $content = XsCertificationSign::getContentMap();

        return compact('limitBigArea', 'limitObject', 'rewardType', 'rewardId', 'giveType', 'vipLevel', 'vipDays', 'expire', 'content');
    }
}