<?php

namespace Imee\Service;

use Imee\Comp\Operate\Auth\Models\Cms\CmsUser;
use Imee\Helper\Traits\SingletonTrait;
use Imee\Models\Config\BbcPushCate;
use Imee\Models\Config\BbcTransformPackage;
use Imee\Models\Recharge\XsExchangeRate;
use Imee\Models\Xs\XsBanner;
use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xs\XsCertificationSign;
use Imee\Models\Xs\XsChatroomBackgroundMall;
use Imee\Models\Xs\XsCommodityAdmin;
use Imee\Models\Xs\XsCustomizePrettyStyle;
use Imee\Models\Xs\XsGift;
use Imee\Models\Xs\XsGiftBag;
use Imee\Models\Xs\XsMedalResource;
use Imee\Models\Xs\XsRoomSpecialEffectsConfig;
use Imee\Models\Xs\XsRoomSkin;
use Imee\Models\Xs\XsRoomTopCard;
use Imee\Models\Xs\XsRoomTopConfig;
use Imee\Models\Xs\XsTopUpActivity;
use Imee\Models\Xs\XsUserForbiddenLog;
use Imee\Models\Xs\XsUserProfile;
use Imee\Models\Xs\XsUserTitleConfigNew;
use Imee\Models\Xsst\XsstVeekaLevelOpLog;
use Imee\Models\Xsst\BmsWhitelistSetting;
use Imee\Service\Operate\Push\PushRuleService;
use Imee\Service\Operate\Roombackground\BackgroundGoodsService;
use Imee\Service\Operate\Roombackground\BackgroundService;

class StatusService
{
    use SingletonTrait;

    const PARAMS_FORMAT = 'label,value';

    public function getPushPackageMap($value = null, $format = '')
    {
        $tmp = BbcTransformPackage::findAll();
        $map = [
            0 => '指定UID'
        ];
        foreach ($tmp as $val) {
            $map[$val['id']] = $val['package'];
        }

        if (!empty($value)) {
            return $map[$value] ?? '';
        }

        if (!empty($format)) {
            $map = self::formatMap($map, $format);
        }

        return $map;
    }

    public function getPushCateMap($value = null, $format = '')
    {
        $tmp = BbcPushCate::getListByWhere([['deleted', 0]]);
        $map = [];
        foreach ($tmp as $val) {
            $map[$val['name']] = $val['name'];
        }

        if (!empty($value)) {
            return $map[$value] ?? '';
        }

        if (!empty($format)) {
            $map = self::formatMap($map, $format);
        }

        return $map;
    }

    public function getAreaMap($value = null, $format = '')
    {
        $map = Helper::getBigareaArr();

        if (!empty($value)) {
            return $map[$value] ?? '';
        }

        if (!empty($format)) {
            $map = self::formatMap($map, $format);
        }

        return $map;
    }

    public function getLanguageMap($value = null, $format = '')
    {
        $map = Helper::getLanguageArr();

        if (!empty($value)) {
            return $map[$value] ?? '';
        }

        if (!empty($format)) {
            $map = self::formatMap($map, $format);
        }

        return $map;
    }

    public function getLanguageNameMap($value = null, $format = '')
    {
        $tmp = Helper::getLanguageArr();
        $tmp = array_keys($tmp);
        //'zh_cn' => 'zh_cn',
        $map = [];
        foreach ($tmp as $val) {
            $map[$val] = $val;
        }

        if (!empty($value)) {
            return $map[$value] ?? '';
        }

        if (!empty($format)) {
            $map = self::formatMap($map, $format);
        }

        return $map;
    }

    public function getTitleNewMap($value = null, $format = '')
    {
        $map = XsUserTitleConfigNew::getAllTitleName();

        if (is_numeric($value)) {
            return $map[$value] ?? '';
        }

        if (!empty($format)) {
            $map = self::formatMap($map, $format);
        }

        return $map;
    }

    public function getBigAreaMap($value = null, $format = '')
    {
        $map = XsBigarea::getBigAreaList();
        $map = array_merge(['all' => '全部大区'], $map);
        if (!empty($value)) {
            return $map[$value] ?? '';
        }

        if (!empty($format)) {
            $map = self::formatMap($map, $format);
        }

        return $map;
    }

    public function getUserDeletedMap($value = null, $format = '')
    {
        $map = XsUserProfile::$deleted_arr;

        if (is_numeric($value)) {
            return $map[$value] ?? '';
        }

        if (!empty($format)) {
            $map = self::formatMap($map, $format);
        }

        return $map;
    }

    public function getMedalMap($value = null, $format = '')
    {
        $result = XsMedalResource::getMedalList(XsMedalResource::HONOR_MEDAL);
        if (!$result) {
            return [];
        }
        $map = [];
        foreach ($result as $val) {
            $map[$val['id']] = $val['id'] . '-' . $val['name'] . '-' . $val['description'];
        }
        if (is_numeric($value)) {
            return $map[$value] ?? '';
        }
        if (!empty($format)) {
            $map = self::formatMap($map, $format);
        }
        return $map;
    }

    public function getCommodityMap($value = null, $format = '')
    {
        $map = XsCommodityAdmin::getCommodityList();
        if (is_numeric($value)) {
            return $map[$value] ?? '';
        }
        if (!empty($format)) {
            $map = self::formatMap($map, $format);
        }
        return $map;
    }

    public function getRoomBackgroundMap($value = null, $format = '')
    {
        $result = XsChatroomBackgroundMall::getListByWhere([
            ['is_free', '=', XsChatroomBackgroundMall::OFF_STATE]
        ], 'bg_id, name');

        if (!$result) {
            return [];
        }
        $map = [];
        foreach ($result as $val) {
            $map[$val['bg_id']] = $val['bg_id'] . '-' . $val['name'];
        }

        if (is_numeric($value)) {
            return $map[$value] ?? '';
        }
        if (!empty($format)) {
            $map = self::formatMap($map, $format);
        }
        return $map;
    }

    public function getUserDurationMap($value = null, $format = '')
    {
        $map = XsUserForbiddenLog::$displayDuration;

        if (is_numeric($value)) {
            return $map[$value] ?? '';
        }

        if (!empty($format)) {
            $map = self::formatMap($map, $format);
        }

        return $map;
    }

    public function getUserMacneedMap($value = null, $format = '')
    {
        $map = XsUserForbiddenLog::$displayMacneed;

        if (is_numeric($value)) {
            return $map[$value] ?? '';
        }

        if (!empty($format)) {
            $map = self::formatMap($map, $format);
        }

        return $map;
    }

    public function getUserMacneedPhoneMap($value = null, $format = '')
    {
        $map = XsUserForbiddenLog::$displayMacneedphone;

        if (is_numeric($value)) {
            return $map[$value] ?? '';
        }

        if (!empty($format)) {
            $map = self::formatMap($map, $format);
        }

        return $map;
    }

    public function getUserForbidReasonMap($value = null, $format = '')
    {
        $arr = XsUserForbiddenLog::$displayGodReason;
        $map = array_combine($arr, $arr);

        if (!empty($value)) {
            return $map[$value] ?? '';
        }

        if (!empty($format)) {
            $map = self::formatMap($map, $format);
        }

        return $map;
    }

    public function getUserPunishAcMap($value = null, $format = '')
    {
        $map = [
            1 => '官方罚款/扣钱',
            2 => '退还罚款/加钱',
            3 => '官方冻结/扣钱',
        ];

        if (is_numeric($value)) {
            return $map[$value] ?? '';
        }

        if (!empty($format)) {
            $map = self::formatMap($map, $format);
        }

        return $map;
    }

    public function getWhiteListTypeMap($value = null, $format = '')
    {
        $map = BmsWhitelistSetting::$whiteListType;

        if (is_numeric($value)) {
            return $map[$value] ?? '';
        }

        if (!empty($format)) {
            $map = self::formatMap($map, $format);
        }

        return $map;
    }

    public function getUidWhitelistTypeMap($value = null, $format = '')
    {
        $map = BmsWhitelistSetting::getWhitelistByType('uid', Helper::getSystemUid());
        if (is_numeric($value)) {
            return $map[$value] ?? '';
        }

        if (!empty($format)) {
            $map = self::formatMap($map, $format);
        }

        return $map;
    }

    public function getDeviceWhitelistTypeMap($value = null, $format = '')
    {
        $data = BmsWhitelistSetting::getWhitelistByType('device', Helper::getSystemUid());
        $map = [];
        foreach ($data as $key => $value) {
            $map[$key] = $key . '-' . $value;
        }
        if (is_numeric($value)) {
            return $map[$value] ?? '';
        }

        if (!empty($format)) {
            $map = self::formatMap($map, $format);
        }

        return $map;
    }

    public function getWhiteListAdminMap($value = null, $format = '')
    {
        $map = CmsUser::getAdminUserMap([], 'user_id, user_name');

        if (is_numeric($value)) {
            return $map[$value] ?? '';
        }

        if (!empty($format)) {
            $map = self::formatMap($map, $format);
        }

        return $map;
    }

    public function getFamilyBigArea($value = null, $format = '')
    {
        $map = XsBigarea::getAllNewBigArea();

        if (is_numeric($value)) {
            return $map[$value] ?? '';
        }

        if (!empty($format)) {
            $map = self::formatMap($map, $format);
        }

        return $map;
    }

    public function getBigAreaAndAllMap($value = null, $format = '')
    {
        $map = XsBigarea::getAllNewBigArea();
        array_unshift($map, '全部大区');
        if (is_numeric($value)) {
            return $map[$value] ?? '';
        }

        if (!empty($format)) {
            $map = self::formatMap($map, $format);
        }

        return $map;
    }

    public function getGreedyEngine($value = null, $format = '')
    {
        $map = XsBigarea::$greedyEngine;

        if (is_numeric($value)) {
            return $map[$value] ?? '';
        }

        if (!empty($format)) {
            $map = self::formatMap($map, $format);
        }

        return $map;
    }

    public function getHorseRaceEngine($value = null, $format = '')
    {
        $map = XsBigarea::$horseRaceEngine;

        if (is_numeric($value)) {
            return $map[$value] ?? '';
        }

        if (!empty($format)) {
            $map = self::formatMap($map, $format);
        }

        return $map;
    }

    public function getMaterialMap($value, $format, $isFree, $source)
    {
        $map = BackgroundService::getMaterialOption($isFree, $source);

        if (is_numeric($value)) {
            return $map[$value] ?? '';
        }

        if (!empty($format)) {
            $map = self::formatMap($map, $format);
        }

        return $map;
    }

    public function getStickerMap($value = null, $format = '', $type = 1)
    {
        $map = XsRoomSpecialEffectsConfig::getIdAndNameMap($type);
        if (is_numeric($value)) {
            return $map[$value] ?? '';
        }

        if (!empty($format)) {
            $map = self::formatMap($map, $format);
        }

        return $map;
    }

    public function getBackgroundMap($value = null, $format = '')
    {
        $map = XsChatroomBackgroundMall::getOptions();

        if (is_numeric($value)) {
            return $map[$value] ?? '';
        }

        if (!empty($format)) {
            $map = self::formatMap($map, $format);
        }

        return $map;
    }

    public function getBigAreaCode($value = null, $format = '')
    {
        $map = XsBigarea::getAllBigAreaCode();

        $list = [];

        foreach ($map as $item) {
            $list[$item] = $item;
        }

        if (is_numeric($value)) {
            return $list[$value] ?? '';
        }

        if (!empty($format)) {
            $list = self::formatMap($list, $format);
        }

        return $list;
    }

    public function getLiveVideoTopStatusMap($value = null, $format = '')
    {
        $map = XsRoomTopConfig::$statusMap;

        if (is_numeric($value)) {
            return $map[$value] ?? '';
        }

        if (!empty($format)) {
            $map = self::formatMap($map, $format);
        }

        return $map;
    }

    public function getLiveVideoTopDeletedMap($value = null, $format = '')
    {
        $map = XsRoomTopConfig::$deletedMap;

        if (is_numeric($value)) {
            return $map[$value] ?? '';
        }

        if (!empty($format)) {
            $map = self::formatMap($map, $format);
        }

        return $map;
    }


    public function getUserPunishReasonMap($value = null, $format = '')
    {
        $map = [
            '房间违规'                 => '房间违规',
            '发布第三方广告'              => '发布第三方广告',
            '越过平台走现金单'             => '越过平台走现金单',
            '发布色情信息'               => '发布色情信息',
            '发布诈骗信息'               => '发布诈骗信息',
            '订单涉黄'                 => '订单涉黄',
            '发布毒品/博彩/投注等违法信息'      => '发布毒品/博彩/投注等违法信息',
            '发布违法信息'               => '发布违法信息',
            '发布反动信息'               => '发布反动信息',
            '一人创建多个技能认证账号'         => '一人创建多个技能认证账号',
            '非本人服务'                => '非本人服务',
            '不按资质和订单规定内容服务'        => '不按资质和订单规定内容服务',
            '资质与本人不符'              => '资质与本人不符',
            '服务态度恶劣'               => '服务态度恶劣',
            '强制用户下多倍单'             => '强制用户下多倍单',
            '未提供订单服务'              => '未提供订单服务',
            '在聊天室进行低俗/色情表演'        => '在聊天室进行低俗/色情表演',
            '在聊天室播放低俗/色情歌曲或配音'     => '在聊天室播放低俗/色情歌曲或配音',
            '在聊天室说低俗/色情擦边词语'       => '在聊天室说低俗/色情擦边词语',
            '在聊天室收到打赏不提供服务'        => '在聊天室收到打赏不提供服务',
            '在聊天室滥用踢人/禁麦等管理权限'     => '在聊天室滥用踢人/禁麦等管理权限',
            '盗用他人信息'               => '盗用他人信息',
            '冒充官方账号'               => '冒充官方账号',
            '头像/昵称/签名含有广告/色情/违法信息' => '头像/昵称/签名含有广告/色情/违法信息',
            '用户提现'                 => '用户提现',
            '私下买卖礼物'               => '私下买卖礼物',
            '未成年打赏'                => '未成年打赏',
            '金额诈骗'                 => '金额诈骗',
            '金额异常'                 => '金额异常',
            '收到打赏不服务'              => '收到打赏不服务',
            '提供违规服务'               => '提供违规服务',
            '买卖礼物诈骗'               => '买卖礼物诈骗',
            '违规代充'                 => '违规代充',
            '魅力值兑换钻石'              => '魅力值兑换钻石',
        ];

        if (!empty($value)) {
            return $map[$value] ?? '';
        }

        if (!empty($format)) {
            $map = self::formatMap($map, $format);
        }

        return $map;
    }

    public function getUserPunishFrozenTypeMap($value = null, $format = '')
    {
        $map = [
            'cancelOrder'  => '撤单',
            'chasingMoney' => '追款',
        ];

        if (!empty($value)) {
            return $map[$value] ?? '';
        }

        if (!empty($format)) {
            $map = self::formatMap($map, $format);
        }

        return $map;
    }

    public function getUserPunishSuperMap($value = null, $format = '')
    {
        $map = [
            '0' => '普通退款(退款金额不能超过罚款)',
            '1' => '无限制退款',
        ];

        if (is_numeric($value)) {
            return $map[$value] ?? '';
        }

        if (!empty($format)) {
            $map = self::formatMap($map, $format);
        }

        return $map;
    }

    public function getUserPunishMoneytoMap($value = null, $format = '')
    {
        $map = [
            '0' => '充值余额',
            '3' => '收入余额',
        ];

        if (is_numeric($value)) {
            return $map[$value] ?? '';
        }

        if (!empty($format)) {
            $map = self::formatMap($map, $format);
        }

        return $map;
    }

    public function getUserLevelTypeMap($value = null, $format = '')
    {
        $map = XsstVeekaLevelOpLog::TYPE_MAP;

        if (is_numeric($value)) {
            return $map[$value] ?? '';
        }

        if (!empty($format)) {
            $map = self::formatMap($map, $format);
        }

        return $map;
    }

    public function getUserOnlineStatusMap($value = null, $format = '')
    {
        $map = XsUserProfile::$onlineStatusArr;

        if (is_numeric($value)) {
            return $map[$value] ?? '';
        }

        if (!empty($format)) {
            $map = self::formatMap($map, $format);
        }

        return $map;
    }

    public function getUserSexMap($value = null, $format = '')
    {
        $map = [
            '1' => '男',
            '2' => '女',
        ];

        if (is_numeric($value)) {
            return $map[$value] ?? '';
        }

        if (!empty($format)) {
            $map = self::formatMap($map, $format);
        }

        return $map;
    }

    public function getSexMap($value = null, $format = '')
    {
        $map = [
            '0' => '未知',
            '1' => '男',
            '2' => '女',
            '3' => '全部',
        ];

        if (is_numeric($value)) {
            return $map[$value] ?? '';
        }

        if (!empty($format)) {
            $map = self::formatMap($map, $format);
        }

        return $map;
    }

    public function getPunishLogOpStateMap($value = null, $format = '')
    {
        $map = [
            1 => '待审核',
            2 => '审核通过',
            3 => '不通过',
        ];

        if (is_numeric($value)) {
            return $map[$value] ?? '';
        }

        if (!empty($format)) {
            $map = self::formatMap($map, $format);
        }

        return $map;
    }

    public static function getPushPlanMap($value = null, $format = '')
    {
        $map = (new PushRuleService())->getPushPlanList();

        if (is_numeric($value)) {
            return $map[$value] ?? '';
        }

        if (!empty($format)) {
            $map = self::formatMap($map, $format);
        }

        return $map;
    }

    public static function getGiftMap($value = null, $format = '')
    {
        $map = XsGift::getGiftIdNameList();

        if (is_numeric($value)) {
            return $map[$value] ?? '';
        }

        if (!empty($format)) {
            $map = self::formatMap($map, $format);
        }

        return $map;
    }

    public function getRoomSkinMap($value = null, $format = '')
    {
        $map = XsRoomSkin::getListIdAndName();

        if (is_numeric($value)) {
            return $map[$value] ?? '';
        }

        if (!empty($format)) {
            $map = self::formatMap($map, $format);
        }

        return $map;
    }

    public function getVipMap($value = null, $format = '')
    {
        $map = XsUserProfile::$vipLevelMap;

        if (is_numeric($value)) {
            return $map[$value] ?? '';
        }

        if (!empty($format)) {
            $map = self::formatMap($map, $format);
        }

        return $map;
    }

    public function getVipDaysMap($value = null, $format = '')
    {
        $map = XsUserProfile::$vipDaysMap;

        if (is_numeric($value)) {
            return $map[$value] ?? '';
        }

        if (!empty($format)) {
            $map = self::formatMap($map, $format);
        }

        return $map;
    }

    public function getGiftBagMap($value = null, $format = '')
    {
        $map = XsGiftBag::getOptions();

        if (is_numeric($value)) {
            return $map[$value] ?? '';
        }

        if (!empty($format)) {
            $map = self::formatMap($map, $format);
        }

        return $map;
    }

    public function getActivityBackgroundMap($value = null, $format = '')
    {
        $map = XsChatroomBackgroundMall::getOptions();

        if (is_numeric($value)) {
            return $map[$value] ?? '';
        }

        if (!empty($format)) {
            $map = self::formatMap($map, $format);
        }

        return $map;
    }

    public function getRoomTopCardMap($value = null, $format = '')
    {
        $map = XsRoomTopCard::getOptions();

        if (is_numeric($value)) {
            return $map[$value] ?? '';
        }

        if (!empty($format)) {
            $map = self::formatMap($map, $format);
        }

        return $map;
    }

    public function getPrettyCardMap($value = null, $format = '')
    {
        $map = XsCustomizePrettyStyle::getOptions();

        if (is_numeric($value)) {
            return $map[$value] ?? '';
        }

        if (!empty($format)) {
            $map = self::formatMap($map, $format);
        }

        return $map;
    }

    public function getCertificationMap($value = null, $format = '')
    {
        $map = XsCertificationSign::getOptions();

        if (is_numeric($value)) {
            return $map[$value] ?? '';
        }

        if (!empty($format)) {
            $map = self::formatMap($map, $format);
        }

        return $map;
    }

    public function getReChargeChannelMap($value = null, $format = '')
    {
        $map = XsTopUpActivity::$channelMap;

        if (is_numeric($value)) {
            return $map[$value] ?? '';
        }

        if (!empty($format)) {
            $map = self::formatMap($map, $format);
        }

        return $map;
    }

    public function getBannerTypeMap($value = null, $format = '')
    {
        $map = XsBanner::$typeMapping;

        if (is_numeric($value)) {
            return $map[$value] ?? '';
        }

        if (!empty($format)) {
            $map = self::formatMap($map, $format);
        }

        return $map;
    }

    public function getBannerStypeMap($value = null, $format = '')
    {
        $map = XsBanner::$stypeMapping;

        if (is_numeric($value)) {
            return $map[$value] ?? '';
        }

        if (!empty($format)) {
            $map = self::formatMap($map, $format);
        }

        return $map;
    }

    public function getBannerRoleMap($value = null, $format = '')
    {
        $map = XsBanner::$roleMapping;

        if (is_numeric($value)) {
            return $map[$value] ?? '';
        }

        if (!empty($format)) {
            $map = self::formatMap($map, $format);
        }

        return $map;
    }

    public function getBannerDisplayPositionMap($value = null, $format = '')
    {
        $map = XsBanner::$displayPositionMapping;

        if (is_numeric($value)) {
            return $map[$value] ?? '';
        }

        if (!empty($format)) {
            $map = self::formatMap($map, $format);
        }

        return $map;
    }

    public function getBannerChatroomPositionMap($value = null, $format = '')
    {
        $map = XsBanner::$positionMapping;

        if (is_numeric($value)) {
            return $map[$value] ?? '';
        }

        if (!empty($format)) {
            $map = self::formatMap($map, $format);
        }

        return $map;
    }

    public function getCateMap($value = null, $format = '')
    {
        $map = XsExchangeRate::CATE_MAP;
        if (is_numeric($value)) {
            return $map[$value] ?? '';
        }

        if (!empty($format)) {
            $map = self::formatMap($map, $format);
        }

        return $map;
    }



    public function getBannerLivePositionMap($value = null, $format = '')
    {
        $map = XsBanner::$positionLiveMapping;

        if (is_numeric($value)) {
            return $map[$value] ?? '';
        }

        if (!empty($format)) {
            $map = self::formatMap($map, $format);
        }

        return $map;
    }

    public function getDeleteMap($value = null, $format = '')
    {
        $map = [
            0 => '未删',
            1 => '已删',
        ];

        if (is_numeric($value)) {
            return $map[$value] ?? '';
        }

        if (!empty($format)) {
            $map = self::formatMap($map, $format);
        }

        return $map;
    }

    public static function getYesAndNoMap($value = null, $format = '')
    {
        $map = [
            0 => '否',
            1 => '是',
        ];
        
        if (is_numeric($value)) {
            return $map[$value] ?? '';
        }

        if (!empty($format)) {
            $map = self::formatMap($map, $format);
        }

        return $map;
    }
    public static function formatMap($map, $format = 'label,value')
    {
        if (empty($map) || empty($format)) {
            return $map;
        }

        $format = explode(',', $format);
        $label = trim($format[0] ?? 'label');
        $value = trim($format[1] ?? 'value');

        $formatRes = [];

        foreach ($map as $k => $v) {
            $formatRes[] = [
                $label => $v,
                $value => is_numeric($k) ? (string)$k : $k,
            ];
        }

        return $formatRes;
    }

    public static function formatMultiple($map, $format)
    {
        if (empty($map)) {
            return $map;
        }

        $format = explode(',', $format);
        $label = $format[0] ?? 'label';
        $value = $format[1] ?? 'children';

        $formatRes = [];

        foreach ($map as $k => $v) {
            $children = [];

            foreach ($v['children'] as $c) {
                $children[] = [
                    'label' => $c['name'],
                    'value' => $c['value'],
                ];
            }

            $formatRes[] = [
                $label => $v['name'],
                $value => $children,
            ];
        }

        return $formatRes;
    }
}