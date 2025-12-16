<?php

namespace Imee\Service\Operate\User;

use Imee\Comp\Common\Fixed\Utility;
use Imee\Comp\Common\Phpnsq\NsqClient;
use Imee\Comp\Common\Redis\RedisBase;
use Imee\Comp\Common\Sdk\SdkSlack;
use Imee\Comp\Operate\Auth\Models\Cms\CmsModuleUserBigarea;
use Imee\Exception\ApiException;
use Imee\Helper\Constant\NsqConstant;
use Imee\Helper\Constant\RiskConstant;
use Imee\Models\Config\BbcTransformUser;
use Imee\Models\Es\ESConfig;
use Imee\Models\recharge\XsIapConfig;
use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xs\XsBroker;
use Imee\Models\Xs\XsBrokerUser;
use Imee\Models\Xs\XsChannel;
use Imee\Models\Xs\XsChatroomPackage;
use Imee\Models\Xs\XsDangerMoney;
use Imee\Models\Xs\XsDelayForbiddenTask;
use Imee\Models\Xs\XsMac;
use Imee\Models\Xs\XsOrder;
use Imee\Models\Xs\XsPay;
use Imee\Models\Xs\XsPayChangeNew;
use Imee\Models\Xs\XsPayPackage;
use Imee\Models\Xs\XsPayPunishLog;
use Imee\Models\Xs\XsRiskCountry;
use Imee\Models\Xs\XsUserBigarea;
use Imee\Models\Xs\XsUserCountry;
use Imee\Models\Xs\XsUserForbidden;
use Imee\Models\Xs\XsUserForbiddenDuration;
use Imee\Models\Xs\XsUserForbiddenLog;
use Imee\Models\Xs\XsUserForbiddenModify;
use Imee\Models\Xs\XsUserForbiddenModifylog;
use Imee\Models\Xs\XsUserIndex;
use Imee\Models\Xs\XsUserModify;
use Imee\Models\Xs\XsUserMoney;
use Imee\Models\Xs\XsUserPretty;
use Imee\Models\Xs\XsUserProfile;
use Imee\Models\Xs\XsUserPunish;
use Imee\Models\Xs\XsUserReviewmod;
use Imee\Models\Xs\XsUserReviewmodlog;
use Imee\Models\Xs\XsUserSafeMobile;
use Imee\Models\Xs\XsUserSettings;
use Imee\Models\Xs\XsUserTitleConfigNew;
use Imee\Models\Xs\XsUserVersion;
use Imee\Models\Xs\XsUserVip;
use Imee\Models\Xss\XsChatMessageNew;
use Imee\Models\Xsst\XsstSessionForbiddenLog;
use Imee\Models\Xsst\XsstSessionForbiddenReasonLog;
use Imee\Models\Xsst\XsstUserForbiddenCheck;
use Imee\Models\Xsst\XsstUserForbiddenReasonLog;
use Imee\Models\Xsst\XsstUserUpdateLangArea;
use Imee\Service\Domain\Service\Risk\RiskUserService;
use Imee\Service\Helper;
use Imee\Service\Operate\User\Pay\PayHistoryAbstractService;
use Imee\Service\Operate\User\UserPriceLevelService;
use Imee\Models\Xs\BaseModel as XsBaseModel;
use Imee\Service\Rpc\PsService;
use Imee\Service\StatusService;
use Phalcon\Di;
use Imee\Service\Operate\User\Money\UserMoneyService;

class UserListService
{
    /**
     * @var PsService $rpcService
     */
    private $rpcService;

    public function __construct()
    {
        $this->rpcService = new PsService();
    }

    public static function getListAndTotal(array $params, bool $isAll = false): array
    {
        $page = intval($params['page'] ?? 1);
        $limit = intval($params['limit'] ?? 15);

        $sort = trim($params['sort'] ?? 'uid');
        $dir = trim($params['dir'] ?? 'desc');
        $adminId = intval($params['admin_id'] ?? 0);

        $order = sprintf("u.%s %s", $sort, $dir);

        $list = ['data' => [], 'total' => 0];

        if (ESConfig::isUseEsQuery()) {
            $sort = ['sort' => $sort, 'dir' => $dir];
            return XsUserProfile::queryUsersEs($params, $page, $limit, $sort, ['sessionLanguage' => Helper::getAdminLanguage($adminId)]);
        }

        // 用户大区信息查询不需要过滤运营大区权限
        $bigAreaId = '';
        if (!$isAll) {
            $bigAreaId = CmsModuleUserBigarea::getBigareaList($adminId, false);
            $bigAreaId = array_keys($bigAreaId);//Helper::getAdminBigArea($adminId);
            
            if (empty($bigAreaId)) {
                return $list;
            }
            $bigAreaId = array_values($bigAreaId);
            if (isset($params['big_area'])) {
                if (in_array($params['big_area'], $bigAreaId)) {
                    $bigAreaId = [$params['big_area']];
                } else {
                    return $list;
                }
            }
            $bigAreaId = implode(',', $bigAreaId);
        } else {
            $prettyId = $params['pretty_uid'] ?? '';
            $uid = $params['uid'] ?? '';

            // 用户大区信息查询必须带参数查询
            if (empty($uid) && empty($prettyId)) {
                return $list;
            }
        }

        $conditions = self::getConditions($params);
        $columns = self::getColumns($params);
        $joinCondition = self::getJoinCondition($params);
        $list = XsUserProfile::getListJoinTable($conditions, $joinCondition, $columns, $order, $page, $limit, $bigAreaId);
        if ($list['total'] == 0) {
            return ['data' => [], 'total' => 0];
        }
        return self::format($list, $params);
    }

    public static function getJoinCondition(array $params): array
    {
        $joinCondition = [
            [
                'class'     => XsUserBigarea::class,
                'condition' => 'b.uid = u.uid',
                'table'     => 'b'
            ],
            [
                'class'     => XsUserSettings::class,
                'condition' => 's.uid = u.uid',
                'table'     => 's'
            ]
        ];
        if (isset($params['channel']) && !empty($params['channel'])) {
            $joinCondition[] = [
                'class'     => XsUserVersion::class,
                'condition' => 'v.uid = u.uid',
                'table'     => 'v'
            ];
        }
        if (isset($params['nvip']) && !empty($params['nvip'])) {
            $joinCondition[] = [
                'class'     => XsUserVip::class,
                'condition' => 'vip.uid = u.uid',
                'table'     => 'vip'
            ];
        }
        return $joinCondition;

    }

    public static function getConditions(array $params): array
    {
        $conditions = [];
        if (isset($params['pretty_uid']) && !empty($params['pretty_uid'])) {
            $prettyUid = self::filterPretty($params['pretty_uid']);
            $prettyUid = XsUserPretty::getUidByPrettyUid($prettyUid);
            empty($prettyUid) && $prettyUid = [0];
            $conditions[] = ['u.uid', 'IN', $prettyUid];
        }
        if (!empty($params['language'])) {
            $conditions[] = ['s.language', '=', $params['language']];
        }
        if (isset($params['channel']) && !empty($params['channel'])) {
            $conditions[] = ['v.channel', '=', $params['channel']];
        }
        if (isset($params['deleted'])
            && !empty($params['deleted'])
            && in_array(intval($params['deleted']), array(-1, 0, 1, 2, 3))) {
            $conditions[] = ['u.deleted', '=', intval($params['deleted'])];
        }
        if (isset($params['role'])
            && !empty($params['role'])
            && in_array(intval($params['role']), array(0, 1, 2, 3))) {
            $conditions[] = ['u.role', '=', intval($params['role'])];
        }
        if (!empty($params['app_id'])) {
            $conditions[] = ['u.app_id', '=', $params['app_id']];
        }
        if (isset($params['uid']) && !empty($params['uid'])) {
            $uids = Helper::formatUid($params['uid']);
            $conditions[] = ['u.uid', 'IN', $uids];
        }
        if (isset($params['name']) && !empty($params['name'])) {
            $conditions[] = ['u.name', '=', $params['name']];
        }
        if (isset($params['sign']) && !empty($params['sign'])) {
            $conditions[] = ['u.sign', '=', $params['sign']];
        }
        if (isset($params['sex']) && is_numeric($params['sex']) && $params['sex'] > 0) {
            $conditions[] = ['u.sex', '=', $params['sex']];
        }
        if (Helper::isDate($params['start'])) {
            $conditions[] = ['u.dateline', '>=', strtotime($params['start'])];
        }

        if (Helper::isDate($params['end'])) {
            $conditions[] = ['u.dateline', '<', strtotime($params['end']) + 86400];
        }

        if (isset($params['nvip']) && !empty($params['nvip'])) {
            $conditions[] = ['vip.level', '=', intval($params['nvip'])];
            $conditions[] = ['vip.vip_expire_time', '>=', time()];
        }

        if (isset($params['svip']) && !empty($params['svip'])) {
            $smoney = XsUserProfile::getLevelMoney($params['svip']);
            $conditions[] = ['u.pay_room_money', '>=', $smoney['min']];
        }
        if (isset($params['evip']) && !empty($params['evip'])) {
            $emoney = XsUserProfile::getLevelMoney($params['evip']);
            $conditions[] = ['u.pay_room_money', '<', $emoney['max']];
        }
        return $conditions;
    }

    private static function filterPretty($pretty): array
    {
        if (empty(trim($pretty))) {
            return [];
        }

        $pretty = str_replace('，', ',', $pretty);
        $pretty = explode(',', trim($pretty, ','));
        $pretty = array_filter(array_map('trim', $pretty));
        $pretty = array_unique($pretty);
        $pretty = array_values($pretty);
        return $pretty;
    }

    public static function getColumns(array $params): array
    {
        $defaultColumns = [
            'u.uid',
            'u.app_id',
            'u.name',
            'u.icon',
            'u.sex',
            'u.video',
            'u.birthday',
            'u.role',
            'u.god_num',
            'u.online_status',
            'u.online_dateline',
            'u.city',
            'u.dateline',
            'u.sign',
            'u.pay_room_money',
            'u.service_score',
            'u.title',
            'u.deleted',
            'u.forbidden_comment',
            'b.bigarea_id',
            's.language',
        ];

        if (!empty($params['channel'])) {
            $defaultColumns = array_merge($defaultColumns, [
                'v.version',
                'v.jversion',
                'v.platform',
                'v.channel'
            ]);
        }

        return $defaultColumns;
    }

    private static function format(array $data, array $params): array
    {
        $uids = Helper::arrayFilter($data['data'], 'uid');
        $userLevel = XsUserVip::getMaxLevelList($uids);
        $userMoney = XsUserMoney::getBatchCommon($uids, [
            'uid', 'money', 'money_b', 'money_cash', 'money_cash_b', 'gold_coin', 'agent_user_money', 'money_banknote', 'money_debts',
            'money_lock', 'money_cash_live', 'money_cash_live_consume', 'money_cash_live_total'
        ], 'uid');
        $dangerMoney = XsDangerMoney::getBatchCommon($uids, ['uid', 'money_receive'], 'uid');
        $titleNew = XsUserTitleConfigNew::getUserTitleNewList();
        $gameAccount = XsUserMoney::getGameAccount($uids);
        $priceLevel = UserPriceLevelService::getInstance()->getList($uids);
        $userLanguageMap = XsUserSettings::getUserSettingBatch($uids);
        $bigarea = XsBigarea::getAreaList();
        $bigarea = array_column($bigarea, $params['lang'] == 'zh_cn' ? 'cn_name' : 'name', 'id');
        $uarea = XsUserCountry::getBatchCommon($uids, ['uid', 'latest_country_code', 'country'], 'uid');
        $brokerUserList = XsBrokerUser::getBrokerUserBatch($uids);
        $brokerList = XsBroker::getBrokerBatch(Helper::arrayFilter($brokerUserList, 'bid'), ['bid', 'bname', 'bigarea_id']);
        $userPrettyList = XsUserPretty::getListByUidArr($uids);
        $now = time();
        if (empty($params['channel'])) {
            $userVersion = XsUserVersion::getBatchCommon($uids, ['version', 'uid', 'platform', 'jversion', 'channel'], 'uid');
        }

        $uids = array_column($data['data'], 'uid');
        $priceLevel = UserPriceLevelService::getInstance()->getList($uids);

        foreach ($data['data'] as &$rec) {
            $uid = $rec['uid'];
            $rec['pretty_uid'] = $userPrettyList[$uid] ?? '';
            $rec['version'] = $rec['version'] ?? ($userVersion[$uid]['version'] ?? '');
            $rec['jversion'] = $userVersion[$uid]['jversion'] ?? ($userVersion['jversion'] ?? '-');
            $rec['title_new'] = (isset($rec['title_new']) && isset($titleNew[$rec['title_new']])) ? $titleNew[$rec['title_new']] : '-';
            $rec['channel'] = $userVersion[$uid]['channel'] ?? ($rec['channel'] ?? '-');
            $rec['platform'] = $userVersion[$uid]['platform'] ?? ($rec['platform'] ?? '-');
            $rec['dateline'] = $rec['dateline'] > 0 ? date('Y-m-d H:i', $rec['dateline']) : ' - ';
            $rec['birthday'] = Helper::formatBirthday($rec['birthday']);
            $rec['online_dateline'] = $rec['online_dateline'] > 0 ? date('Y-m-d H:i', $rec['online_dateline']) : ' - ';
            $rec['app_name'] = isset($rec['app_id']) ? Helper::getAppName($rec['app_id']) : 'PartyStar';

            if (!empty($rec['icon'])) {
                $rec['icon'] = PARTYING_OSS . $rec['icon'];
            } else {
                $rec['icon'] = '';
            }

            if (!empty($rec['video'])) {
                $rec['video'] = PARTYING_OSS . $rec['video'];
            } else {
                $rec['video'] = '';
            }

            $rec['money'] = round(($userMoney[$uid]['money'] ?? 0) / 100, 2); // 钻石
            $rec['money_b'] = round(($userMoney[$uid]['money_b'] ?? 0) / 100, 2); // 虚拟币
            $rec['money_cash'] = round((($userMoney[$uid]['money_cash'] ?? 0) + ($userMoney[$uid]['money_cash_b'] ?? 0)) / 100, 2);  // 魅力值
            $rec['money_agent'] = round(($userMoney[$uid]['agent_user_money'] ?? 0) / 100, 2); // 转账钻石
            $rec['money_banknote'] = round(($userMoney[$uid]['money_banknote'] ?? 0) / 10000, 4); // 现金/美元
            $rec['gold_coin'] = round(($userMoney[$uid]['gold_coin'] ?? 0) / 100, 2); // 金豆
            $rec['money_cash_live'] = round(($userMoney[$uid]['money_cash_live'] ?? 0) / 100, 2); // 直播魅力值
            $rec['gold'] = $gameAccount[$uid]['Char.Coin'] ?? 0; // 游戏货币账户-金币
//            $rec['chips'] = $gameAccount[$uid]['Char.Chips'] ?? 0; // 游戏货币账户-筹码
            $rec['money_lock'] = round(($userMoney[$uid]['money_lock'] ?? 0) / 100, 2); // 锁定
            $rec['money_debts'] = round(($userMoney[$uid]['money_debts'] ?? 0) / 100, 2); // 欠款
            $rec['danger_receive'] = round(($dangerMoney[$uid]['money_receive'] ?? 0) / 100, 2); // 风险币
            $rec['uarea'] = '-';
            if (isset($uarea[$uid])) {
                if ($params['lang'] == 'en') {
                    $rec['uarea'] = $uarea[$uid]['latest_country_code'];
                } else {
                    $rec['uarea'] = $uarea[$uid]['country'];
                }
            }
            list($rec['uarea'], $rec['city']) = Helper::transformAreaToArray($rec['uarea'] ?? '', $rec['city'] ?? '');

            $rec['vip'] = $priceLevel[$uid] ?? 0;
            $rec['pay_room_money'] = $rec['pay_room_money'] / 100;
            $rec['is_service_forbidden'] = (isset($rec['service_forbidden_dateline']) && $rec['service_forbidden_dateline'] > $now) ? 1 : 0;
            $rec['language'] = ($userLanguageMap[$rec['uid']] ?? [])['language'] ?? '';
            $rec['language'] = Helper::getLanguageName($rec['language']);
            $rec['bname'] = XsBrokerUser::getBrokerNameByUid($rec['uid']);
            $bid = ($brokerUserList[$rec['uid']] ?? [])['bid'] ?? 0;
            $broker = $brokerList[$bid] ?? [];
            if ($broker) {
                $rec['bid'] = $broker['bid'];
                $rec['bname'] = $broker['bname'];
                $rec['barea'] = $bigarea[$broker['bigarea_id']] ?? '';
            }
            $rec['price_level'] = $priceLevel[$rec['uid']] ?? 0;
            $rec['big_area'] = $bigarea[$rec['bigarea_id']] ?? '-';
            $rec['nvip'] = $userLevel[$uid] ?? 0;

            if ($rec['deleted'] == 1 && $rec['forbidden_comment'] == 1) {
                $rec['deleted'] = 1000;
            }
        }

        return [
            'data'       => $data['data'],
            'total'      => $data['total'],
            'priceLevel' => $priceLevel
        ];
    }


    public function userResetToken(array $params): array
    {
        $uid = array_get($params, 'uid', []);
        $uidArr = Helper::formatIds(is_array($uid) ? $uid : [$uid]);

        foreach ($uidArr as $_uid) {
            $index = XsUserIndex::findFirst($_uid);
            if ($index) {
                $index->salt = Utility::random(10);;
                $index->salt_web = Utility::random(10);;
                $index->save();
            }
        }

        return [true, ''];
    }

    public function userResetName(array $params): array
    {
        $uid = array_get($params, 'uid', []);
        $uidArr = Helper::formatIds(is_array($uid) ? $uid : [$uid]);

        foreach ($uidArr as $_uid) {
            NsqClient::publish(NsqConstant::TOPIC_XS_ADMIN, array(
                'cmd'  => 'reset.name',
                'data' => array(
                    'uid' => $_uid,
                )
            ));
        }
        return [true, ''];
    }

    public function userReplaceName(array $params): array
    {
        $uid = array_get($params, 'uid', []);
        $uidArr = Helper::formatIds(is_array($uid) ? $uid : [$uid]);

        $from = trim($params['from'] ?? '');
        $to = trim($params['to'] ?? '');

        foreach ($uidArr as $_uid) {
            NsqClient::publish(NsqConstant::TOPIC_XS_ADMIN, array(
                'cmd'  => 'replace.name',
                'data' => array(
                    'uid'  => $_uid,
                    'from' => $from,
                    'to'   => $to,
                )
            ));
        }
        return [true, ''];
    }

    public function userResetSign(array $params): array
    {
        $uid = array_get($params, 'uid', []);
        $uidArr = Helper::formatIds(is_array($uid) ? $uid : [$uid]);

        foreach ($uidArr as $_uid) {
            NsqClient::publish(NsqConstant::TOPIC_XS_ADMIN, array(
                'cmd'  => 'reset.sign',
                'data' => array(
                    'uid' => $_uid,
                )
            ));
        }
        return [true, ''];
    }

    public function updateUserArea(array $params): array
    {
        $uid = intval($params['uid'] ?? 0);
        $area = trim($params['area'] ?? '');
        $oldarea = trim($params['oldarea'] ?? '');
        $remarks = trim($params['remarks'] ?? '');
        $adminId = trim($params['admin_id'] ?? '');

        if (empty(XsUserProfile::findOne($uid))) {
            return [false, '用户不存在'];
        }
        $objArea = XsRiskCountry::findFirst(array(
            'columns'    => 'name_cn, alpha_2',
            'conditions' => 'alpha_2 = :area:',
            'bind'       => [
                'area' => $area
            ]
        ));
        if (empty($objArea)) {
            return [false, '修改地区不存在'];
        }

        $userInfo = XsUserCountry::findFirst($uid);
        $old = '';
        if ($userInfo) {
            $old = $userInfo->country;
        }
        $data = [
            'uid'                 => (int)$uid,
            'country'             => $objArea->name_cn,
            'latest_country'      => $objArea->name_cn,
            'latest_country_code' => $objArea->alpha_2,
        ];
        [$res, $msg] = (new PsService())->reLocateUserCountry($data);
        if (!$res) {
            return [false, $msg];
        }

        $notify = [
            'type' => 'user.country',
            'data' => [
                'uid'  => (int)$uid,
                'from' => $old,
                'to'   => $userInfo->country,
                'by'   => 'cms'
            ]
        ];
//        NsqClient::publish(NsqConstant::TOPIC_PROPERTY, $notify);

        $key = "Xs.User.Country." . $uid;
        $redis = new RedisBase(RedisBase::REDIS_CACHE);
        $redis->delete($key);

        $obj = new XsstUserUpdateLangArea();
        $obj->uid = $uid;
        $obj->admin = $adminId;
        $obj->type = 2;
        $obj->remarks = $remarks;
        $obj->dateline = time();
        $obj->log = $oldarea . '=>' . $objArea->name_cn;
        $obj->create();

        return [true, ''];
    }

    public function updateUserLanguage(array $params): array
    {
        $uid = intval($params['uid'] ?? 0);
        $language = trim($params['language'] ?? '');
        $oldlanguage = trim($params['oldlanguage'] ?? '');
        $remarks = trim($params['remarks'] ?? '');
        $adminId = trim($params['admin_id'] ?? '');

        $languageArr = Helper::getLanguageArr();

        if (empty(XsUserProfile::findOne($uid))) {
            return [false, '用户不存在'];
        }
        if (empty($language) || !isset($languageArr[$language])) {
            return [false, '请选择语言'];
        }


        $userInfo = XsUserSettings::findFirst($uid);
        if (empty($userInfo)) {
            $userInfo = new XsUserSettings();
            $userInfo->uid = $uid;
        } elseif ($userInfo->language == $language) {
            return [true, ''];
        }

        $old = $userInfo->language ?? '';
        $userInfo->language = $language;

        if (!$userInfo->save()) {
            return [false, '保存错误'];
        }

        $notify = [
            'type' => 'user.language',
            'data' => [
                'uid'  => (int)$uid,
                'from' => $old,
                'to'   => $language,
                'by'   => 'cms'
            ]
        ];
//        NsqClient::publish(NsqConstant::TOPIC_PROPERTY, $notify);
        $key = "Ots.User.Settings." . $uid;
        $redis = new RedisBase(RedisBase::REDIS_CACHE);
        $redis->hDel($key, 'language');

        $obj = new XsstUserUpdateLangArea();
        $obj->uid = $uid;
        $obj->admin = $adminId;
        $obj->type = 1;
        $obj->remarks = $remarks;
        $obj->dateline = time();
        $obj->log = $oldlanguage . '=>' . $languageArr[$language];
        $obj->create();
        BbcTransformUser::updateData(compact('uid', 'language'));

        return [true, ''];
    }

    public function updateUserBigArea(array $params): array
    {
        $uid = intval($params['uid'] ?? 0);
        $bigArea = trim($params['big_area'] ?? '');
        $adminId = trim($params['admin_id'] ?? '');

        $areaArr = XsBigarea::AREA_MAP;
        if (empty($bigArea) || !isset($areaArr[$bigArea])) {
            return [false, '请选择大区'];
        }
        if (empty(XsUserProfile::findOne($uid))) {
            return [false, '用户不存在'];
        }

        $userInfo = XsUserBigarea::findFirst($uid);
        if (empty($userInfo)) {
            $userInfo = new XsUserBigarea();
            $userInfo->uid = $uid;
        } elseif ($userInfo->bigarea_id == $bigArea) {
            return [true, ''];
        }

        $oldBigArea = $areaArr[$userInfo->bigarea_id];
        $userInfo->bigarea_id = $bigArea;
        $userInfo->by = 'cms';
        if (!$userInfo->save()) {
            return [false, '保存错误'];
        }

        $addLogData = [
            'uid'      => $uid,
            'admin'    => $adminId,
            'type'     => XsstUserUpdateLangArea::BIG_AREA,
            'dateline' => time(),
            'log'      => $oldBigArea . '=>' . $areaArr[$bigArea]
        ];
        XsstUserUpdateLangArea::add($addLogData);

        return [true, ''];
    }


    public function priceLevel(array $params): array
    {
        $adminId = intval($params['admin_id'] ?? 0);
        list($flg, $rec) = UserPriceLevelService::getInstance()->update($params, $adminId);
        return [$flg, $flg ? [] : $rec];
    }

    public function getPriceLevelLog(array $params): array
    {
        $uid = intval($params['uid'] ?? 0);
        return UserPriceLevelService::getInstance()->getHistory($uid);
    }

    public function getLangAndAreaLog(array $params): array
    {
        $uid = intval($params['uid'] ?? 0);
        $data = XsstUserUpdateLangArea::find([
            'conditions' => 'uid = :uid:',
            'bind'       => array(
                'uid' => $uid
            )
        ])->toArray();
        if (count($data)) {
            $typeMap = [
                1 => '修改语言',
                2 => '修改地区',
                3 => '修改大区'
            ];
            foreach ($data as &$v) {
                $v['dateline'] = $v['dateline'] ? date('Y-m-d H:i:s', $v['dateline']) : '';
                $v['admin'] = Helper::getAdminName($v['admin'] ?? '-');
                $v['type'] = $typeMap[$v['type'] ?? ''] ?? '-';
            }
        }
        return ['data' => $data, 'total' => count($data)];
    }

    public function getUserForbiddenLog(array $params): array
    {
        $uid = intval($params['uid'] ?? 0);
        $data = XsUserForbiddenLog::getListAndTotal([['uid', '=', $uid]], '*', 'id asc');
        $allSource = XsUserForbiddenLog::$source_arr;

        $deleteMap = [
            '解封',
            '禁搜',
            '禁聊',
            '禁登录',
            '',
            '',
            '',
            '',
            '',
            '',
            '解除禁止接单',
            '禁止接单'
        ];


        foreach ($data['data'] as &$val) {
            $_extra = $val['extra'] ? @json_decode($val['extra'] ?? '', true) : [];
            $val['dateline'] = $val['dateline'] ? date('Y-m-d H:i:s', $val['dateline']) : '';
            if ($val['op'] == 0) {
                $val['op_name'] = '系统';
            } else {
                $val['op_name'] = Helper::getAdminName($val['op'] ?? '');
            }
            $val['source_name'] = isset($allSource[$val['source']]) ? $allSource[$val['source']] : '';

            if ($val['deleted'] == 1 && isset($_extra['forbidden_comment']) && $_extra['forbidden_comment'] == 1) {
                $val['deleted'] = '不可被搜索且房间内（直播间&语音房）禁言';
            } else {
                $val['deleted'] = $deleteMap[$val['deleted'] ?? ''] ?? '';
            }
        }


        $displayGodReasonMap = [];
        foreach (XsUserForbiddenLog::$displayGodReason as $v) {
            $displayGodReasonMap[$v] = $v;
        }
        $displayReasonMap = [];
        foreach (XsUserForbiddenLog::$displayReason as $v) {
            $displayReasonMap[$v] = $v;
        }

        $data['reason'] = StatusService::formatMap($displayReasonMap);
        $data['god_reason'] = StatusService::formatMap($displayGodReasonMap);

        return $data;
    }

    public function getUserForbiddenDuration(array $params): array
    {
        $uid = intval($params['uid'] ?? 0);
        $row = XsUserForbiddenDuration::findOne($uid);
        if (!empty($row)) {
            $duration = date('Y-m-d H:i', intval($row['dateline']));
        } else {
            $duration = '永久';
        }
        $mac = '';
        $imei = '';
        $macNeed = 0;

        $versionData = XsUserVersion::findFirst(array(
            "uid=:uid:",
            "bind" => array("uid" => $uid)
        ));
        if ($versionData) {
            $mac = $versionData->mac;
            $macNeedData = XsUserForbidden::findOneByWhere([['mac', '=', $mac]]);
        }

        if (!empty($mac)) {
            $macData = XsMac::find(array(
                "uid = '{$uid}' or mac = '{$mac}'",
                'order'  => 'id desc',
                'offset' => 0,
                'limit'  => 10,
            ))->toArray();
        } else {
            $macData = XsMac::find(array(
                "uid = '{$uid}'",
                'order'  => 'id desc',
                'offset' => 0,
                'limit'  => 10,
            ))->toArray();
        }

        if ($macData) {
            foreach ($macData as $mk => $mv) {
                if (!empty($mv['imei'])) {
                    $imei = $mv['imei'];
                    break;
                }
            }
            if (!empty($imei)) {
                $macNeedData2 = XsUserForbidden::findOneByWhere([['mac', '=', $imei]]);
            }
        }

        if (!empty($macNeedData) || !empty($macNeedData2)) {
            $macNeed = 1;
        }
        $userInfo = XsUserSettings::findFirst($uid);
        if ($userInfo) {
            $did = $userInfo->did;
        } else {
            $did = '';
        }
        $totalIncome = 0;
        // 查询订单收入
        $income = XsOrder::sum(array(
            "column" => "money",
            "to=:to: and iscomplete>0 and money>0 and (state='service_complete' OR (state='appeal_complete' AND isappeal=1))",
            "bind"   => array("to" => $uid),
        ));
        $totalIncome = $totalIncome + ($income / 100);

        // 查询聊天室打赏收入
        $roomincome = XsChatroomPackage::sum(array(
            "column" => "money",
            "uid=:to:",
            "bind"   => array("to" => $uid),
        ));
        $totalIncome = $totalIncome + ($roomincome / 100);

        // 查询其它收入（目前仅聊天红包）
        $otherincome = XsPayPackage::sum(array(
            "column" => "money",
            "to=:to: and status='complete'",
            "bind"   => array("to" => $uid),
        ));
        $totalIncome = $totalIncome + ($otherincome / 100);

        return array(
            //'duration'           => $duration,
            'forbidden_duration' => $duration,
            'mac'                => $mac,
            'imei'               => $imei,
            'did'                => $did,
            'macneed'            => $macNeed,
            'income_money'       => $totalIncome,
        );
    }

    public function userForbiddenCheck(array $params): array
    {
        $uid = intval($params['uid'] ?? 0);
        list($flg, $rec) = $this->rpcService->getUserVip(['uid' => $uid]);
        if (!$flg) {
            return [false, $rec];
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

        $data = [
            'uid'                   => $uid,
            'level'                 => $rec['level'] ?? 0,
            'forbidden_delay_hours' => $rec['forbidden_delay_hours'] ?? 18,
            'has_delay_forbidden'   => !empty($forbiddenTask),
            'forbidden_start_time'  => $startTime,
        ];

        return [true, $data];
    }

    public function userForbidden(array $params): array
    {
        $deleted = intval($params['deleted'] ?? 0);
        $uid = intval($params['uid'] ?? 0);
        $duration = intval($params['duration'] ?? 0);
        $reason = trim($params['reason'] ?? '');
        $originReason = $reason;

        $mark = trim($params['mark'] ?? '');

        $macData = trim($params['mac'] ?? '');
        $imeiData = trim($params['imei'] ?? '');
        $didData = trim($params['did'] ?? '');

        $macNeedData = intval($params['macneed'] ?? 0);
        $macNeedPhone = intval($params['macneedphone'] ?? 0);
        $source = intval($params['source'] ?? 0);
        $language = trim($params['language'] ?? '');
        $opCheck = intval($params['op_check'] ?? 0);////核查ID
        $forbiddenSource = trim($params['forbidden_source'] ?? '');
        $ruleType = intval($params['ruleType'] ?? 0);
        $mustForbidden = intval($params['must_forbidden'] ?? 1);

        $adminId = intval($params['admin_id'] ?? 0);
        $appId = APP_ID; // 同步安全手机号，指定APP

        $forbiddenDevice = $macNeedData > 0 ? 1 : 0;
        $mac = '';
        $imei = '';
        $did = '';

        //运营后台的下拉选项新增 (不可搜索不可发言)，当运营选择该选项时，传给nsq的字段里面多传一个属性字段 forbidden_comment = 1，但deleted仍然传 1
        $forbiddenComment = 0;
        if($deleted == 1000){
            $deleted = 1;
            $forbiddenComment = 1;
        }

        if ($macData && ($deleted == 0 || $macNeedData > 0)) {
            $mac = $macData;
        }
        if ($imeiData && ($deleted == 0 || $macNeedData > 0)) {
            $imei = $imeiData;
        }
        if ($didData && ($deleted == 0 || $macNeedData > 0)) {
            $did = $didData;
        }

        if ($deleted > 0 && $deleted < 3 && $macNeedData > 0) {
            return [false, '非禁止登录的不可操作封设备'];
        }

        if (empty($reason)) {
            return [false, '请选择操作原因'];
        }

        if (!empty($opCheck)) { //存在op check 就是封禁复查审核
            $log = XsstUserForbiddenCheck::findFirst(array(" log_id='$opCheck'"));
            if ($log) {
                return [false, '该记录已处理'];
            }
        }

        if ($deleted >= 0 && $deleted <= 3) {
            $profile = XsUserProfile::findFirst($uid);
            if ($profile) {
                if (($profile->deleted == 0 && in_array($deleted, array(1, 2, 3))) ||
                    ($profile->deleted == 1 && in_array($deleted, array(0, 2, 3))) ||
                    ($profile->deleted == 2 && in_array($deleted, array(0, 3))) ||
                    ($profile->deleted == 3 && in_array($deleted, array(0, 2, 3)))
                ) {
                    if ($profile->deleted != $deleted && $deleted > 1 && empty($reason)) {
                        return [false, '请选择操作原因'];
                    }
                    $reason = $this->_translate($uid, $reason);

                    // 判断是否需要同步安全手机号
                    if ($macNeedPhone == 1) {

                        // 通过uid在xs_user_safe_mobile中查出与之同号码的uid
                        $result = XsUserSafeMobile::findFirstValue($uid);
                        if (!$result) {
                            return [false, 'TA没有安全手机号'];
                        }
                    }
                } else {
                    return [false, '流程不对'];
                }
            }
        }

        $c = trim($params['c'] ?? '');
        if ($c == 'check') {
            return $this->userForbiddenCheck($params);
        }

        if ($deleted >= 0 && $deleted <= 3) {
            $profile = XsUserProfile::findFirst($uid);
            if ($profile) {
                if (($profile->deleted == 0 && in_array($deleted, array(1, 2, 3))) ||
                    ($profile->deleted == 1 && in_array($deleted, array(0, 2, 3))) ||
                    ($profile->deleted == 2 && in_array($deleted, array(0, 3))) ||
                    ($profile->deleted == 3 && in_array($deleted, array(0, 2, 3)))
                ) {
                    if ($profile->deleted != $deleted && $deleted > 1 && empty($reason)) {
                        return [false, '请选择操作原因'];
                    }
                    $reason = $this->_translate($uid, $reason);

                    // 判断是否需要同步安全手机号
                    if ($macNeedPhone == 1) {

                        // 通过uid在xs_user_safe_mobile中查出与之同号码的uid
                        $result = XsUserSafeMobile::findFirstValue($uid);
                        if (!$result) {
                            return [false, 'TA没有安全手机号'];
                        }
                        $uidArray = [];
                        if (!$appId) {
                            $uidArray = XsUserSafeMobile::findUids($result->mobile);
                        } else if (is_array($appId)) {
                            $uidArray = XsUserSafeMobile::findAppUids($result->mobile, $appId);
                        }else if (is_numeric($appId)) {
                            $uidArray = XsUserSafeMobile::findAppUids($result->mobile, [$appId]);
                        }

                        if (!empty($uidArray)) {
                            foreach ($uidArray as $k => $v) {
                                $reason = $this->_translate($v, $reason);
                                $tmpData = array(
                                    'uid'      => intval($v),
                                    'deleted'  => $deleted,
                                    'duration' => $duration,
                                    'reason'   => $reason,
                                    'remark'   => '（因为' . $uid . '安全手机号关联封禁)' . $mark,
                                    'op'       => $adminId,
                                    'mac'      => '',
                                    'imei'     => '',
                                    'did'      => '',
                                    'device'   => 0,
                                    'check'    => 0,
                                    'source'   => $forbiddenSource,
                                    'must_forbidden' => !!$mustForbidden,
                                    'operator' => Helper::getAdminName($adminId),
                                );

                                if ($v == $uid) {
                                    $tmpData['mark'] = $mark;
                                    $tmpData['mac'] = $mac;
                                    $tmpData['imei'] = $imei;
                                    $tmpData['did'] = $did;
                                    $tmpData['device'] = $forbiddenDevice;
                                    $tmpData['check'] = $opCheck > 0 ? 1 : 0;
                                }
                                $forbiddenComment && $tmpData['forbidden_comment'] = 1;

                                $this->rpcService->forbiddenUser($tmpData);

//                                NsqClient::publish(NsqConstant::TOPIC_XS_CMD, array(
//                                    'cmd'  => 'forbidden',
//                                    'data' => $tmpData
//                                ));
                            }
                        }

                    } else {

                        $tmpData = array(
                            'uid'            => $uid,
                            'deleted'        => $deleted,
                            'duration'       => $duration,
                            'op'             => $adminId,
                            'operator' => Helper::getAdminName($adminId),
                            'reason'         => $reason,
                            'remark'         => $mark,
                            'device'         => $forbiddenDevice,
                            'check'          => $opCheck > 0 ? 1 : 0,
                            'source'         => $forbiddenSource,
                            'lang_reason'    => '',
                            'must_forbidden' => !!$mustForbidden,
                            'mac'            => $mac,
                            'imei'           => $imei,
                            'did'            => $did,
                        );
                        $forbiddenComment && $tmpData['forbidden_comment'] = 1;

                        list($flg, $rec) = $this->rpcService->forbiddenUser($tmpData);
                        if(!$flg){
                            throw new ApiException(ApiException::MSG_ERROR, $rec);
                        }

//                        //发送消息到xs，由xs更改数据
//                        NsqClient::publish(NsqConstant::TOPIC_XS_CMD, array(
//                            'cmd'  => 'forbidden',
//                            'data' => $tmpData
//                        ));

                    }
                } else {
                    return [false, '流程不对'];
                }
            }
        }

        if ($source) {
            if ($deleted == 3 && $duration > 0) {
                $this->_forbiddenLog(array(
                    'type'      => 2,
                    'op_id'     => $adminId,
                    'handle_id' => $uid,
                    'source'    => $source,
                    'language'  => $language
                ));
            }

            //会话封禁原因记录
            XsstSessionForbiddenReasonLog::saveRows(array(
                'reason'   => $reason,
                'source'   => $source,
                'uid'      => $uid,
                'language' => $language
            ));
        }

        //风险用户的封禁日志,同source
        if ($forbiddenSource == XsUserForbiddenLog::FORBIDDEN_SOURCE_USER_REAUDIT && $deleted == 3 && $duration > 0) {
            RiskUserService::record($uid, $adminId, RiskConstant::RISK_OP_TYPE_FORBIDDEN, $ruleType, $duration, $reason, $language);
        }

        if (!empty($opCheck)) { //存在op check 就是封禁复查审核
            $status = $deleted <= 1 ? 3 : 2;
            XsstUserForbiddenCheck::userForbiddenModify($adminId, $uid, $opCheck, $status, $reason);
        }
        if (empty($language)) {
            $user = XsUserSettings::findFirst($uid);
            if ($user) {
                $language = $user->language;
            }
        }

        XsstUserForbiddenReasonLog::saveRows(array(
            'reason'   => $originReason,
            'uid'      => $uid,
            'language' => $language,
            'op_id'    => $adminId,
        ));

        //境外诈骗团伙
        if ($originReason == "境外诈骗团伙") {
            //查出与用户聊过天的（中文区用户）
            $res = XsChatMessageNew::find(array(
                "columns"    => "DISTINCT to_user_id",
                "conditions" => "from_user_id = :from_user_id:",
                "bind"       => array(
                    "from_user_id" => $uid
                )
            ))->toArray();
            if (!empty($res)) {
                $profile = XsUserProfile::findFirst($uid);
                foreach ($res as $v) {
                    $language = XsUserSettings::getLanguage($v['to_user_id']);
                    if ($language == 'zh_tw' || $language == 'zh_cn') {
                        $msg = "亲爱的用户，我们通过系统识别，您的好友/联系人 【@{$profile->name} @ID：{$uid}】可能是境外诈骗团伙成员，Partying特此提醒您，在聊天交友过程中，谨慎加第三方聊天软件，不要进行任何第三方资金转账，充值操作（包括虚拟货币、数字货币、彩票、博彩等形式）。建议增强自我防范意识，避免上当受骗！";
                        $this->_sendMessage($v['to_user_id'], $msg);
                    }
                }

            }
            return [true, ''];
        }

        return [true, ''];
    }

    public
    function getOrderChatLog(array $params): array
    {
        $page = intval($params['page'] ?? 1);
        $limit = intval($params['limit'] ?? 15);

        $from = intval($params['uid'] ?? 0);

        $profile = XsUserProfile::findFirstValue($from);
        $num = XsChatMessageNew::count(array(
            "from_user_id = '{$from}'",
        ));

        //优化offset过大引起的慢查询
        $resIds = XsChatMessageNew::query()
            ->columns('id')
            ->where("from_user_id = :from_user_id:", ['from_user_id' => $from])
            ->orderBy('id desc')
            ->limit($limit, $limit * ($page - 1))
            ->execute()
            ->toArray();
        if (empty($resIds)) {
            return ['data' => [], 'total' => 0];
        }

        $ids = array_column($resIds, 'id');

        $res = XsChatMessageNew::query()
            ->inWhere('id', $ids)
            ->orderBy('id desc')
            ->execute()
            ->toArray();

        foreach ($res as &$rec) {
            $rec['dateline'] = date('Y-n-d H:i:s', $rec['dateline']);
            $rec['from_name'] = $profile->name;
            $rec['to_name'] = $rec['to_user_id'];
            $rec['content_html'] = $this->getContentHtml($rec);
        }
        return ['data' => $res, 'total' => intval($num)];
    }

    /**
     * 格式话聊天消息
     * @param array $record
     * @return string
     */
    private function getContentHtml(array $record): string
    {
        $contentStrObj = json_decode($record['content'], true);
        $objectName = $record['object_name'];

        $content = $contentStrObj['content'] ?? "";
        $extra = json_decode($contentStrObj['extra'], true);
        $imageUri = $contentStrObj['imageUri'] ?? "";
        $thumbUri = $contentStrObj['thumbUri'] ?? "";
        $voiceUrl = $contentStrObj['voiceUrl'] ?? "";
        $poi = $contentStrObj['poi'] ?? "";
        $longitude = $contentStrObj['longitude'] ?? "";
        $latitude = $contentStrObj['latitude'] ?? "";

        $icon = $extra['icon'] ?? "";
        $type = $extra['type'] ?? "";
        $name = $extra['name'] ?? "";

        switch ($objectName) {
            case 'RC:TxtMsg':
                if ($icon) {
                    $realContent = "<img src='" . $icon . "' alt='' style='max-width: 100px; max-height: 100px;'/>";
                } elseif ($type == "game") {
                    $realContent = "<div class='display-date'>
                                    <div class='display-date-image' style='background-image: url(https://xs-image.partying.tw/" . $icon . ");'></div>
                                    <div class='display-date-desc'>
                                        <h4>" . $name . "</h4>
                                    </div>
                                </div>";
                } else {
                    $realContent = $content;
                }
                break;

            case 'RC:ImgMsg':
                $imageSrc = $thumbUri ?: $imageUri;
                $realContent = "<img width='100' src='" . $imageSrc . "' alt=''/>";
                break;

            case 'RC:VcMsg':
                if (strpos($content, "http") === 0) {
                    $audioSrc = $content;
                } elseif (!empty($voiceUrl)) {
                    $audioSrc = $voiceUrl;
                } else {
                    $audioSrc = "data:audio/wav;base64," . $content;
                }
                $realContent = "<audio controls src='" . $audioSrc . "'></audio>";
                break;

            case 'RC:LBSMsg':
                if ($content) {
                    $realContent = "<div style='float: left;'>
                                    <h3>" . $poi . "</h3>
                                    <p>
                                        <img src='data:image/png;base64," . $content . "' style='display: block; height: 200px;'/>
                                    </p>
                                </div>";
                } else {
                    $markerURI = "https://xs.im-ee.com/public/static/icon/map_loc.png";
                    $uri = "http://restapi.amap.com/v3/staticmap?markers=-1," . urlencode($markerURI) . ",0:" . $longitude . "," . $latitude . "&size=200*200&key=16a8bc4c6d753f23aa2e9ee602b3bb2d&zoom=14";
                    $realContent = "<div style='float: left;'>
                                    <h3>" . $poi . "</h3>
                                    <p>
                                        <img src='" . $uri . "' style='display: block; height: 200px;'/>
                                    </p>
                                </div>";
                }
                break;

            default:
                $realContent = "";
                break;
        }

        return "<div style='width: 300px; white-space: break-spaces; word-break: break-all;'>$realContent</div>";
    }

    public function getOptions(array $params): array
    {
        $lang = trim($params['lang'] ?? '');

        $language = Helper::getLanguageArr();
        $role = ['' => '全部角色', '0' => '待注册', '1' => '用户', '2' => '大神关闭接单', '3' => '大神'];
        $deleted = ['' => '全部状态', '-1' => '机器', '0' => '正常', '1' => '不能被搜索', '2' => '禁止聊天', '3' => '禁止登录'];
        $sex = ['' => '全部', '1' => '男', '2' => '女'];


        $chanel = XsChannel::find(array(
            'deleted=0 and state=1',
            'order'   => 'id asc',
            'columns' => 'id,channel,name'
        ))->toArray();
        $chanel = array_column($chanel, 'name', 'channel');

        $titles = XsUserTitleConfigNew::find([
            'columns' => 'level,name',
            'order'   => 'level asc'
        ])->toArray();
        $titles = array_column($titles, 'name', 'level');


        $areaArr = XsRiskCountry::find(array(
            'columns' => 'name_cn,alpha_2'
        ))->toArray();

        $areaArr = $lang == 'en' ? array_column($areaArr, 'alpha_2', 'alpha_2') : array_column($areaArr, 'name_cn', 'alpha_2');


        return [
            'language'  => StatusService::formatMap($language),
            'role'      => StatusService::formatMap($role),
            'deleted'   => StatusService::formatMap($deleted),
            'channel'   => StatusService::formatMap($chanel),
            'sex'       => StatusService::formatMap($sex),
            'title_new' => StatusService::formatMap($titles),
            'area'      => StatusService::formatMap($areaArr),
            'big_area'  => StatusService::formatMap(XsBigarea::AREA_MAP),
            'fifty'     => XsUserProfile::hasFiftyUserLevelPurview() ? 1 : 0,
        ];
    }

    private function _forbiddenLog($data)
    {
        $user = XsUserProfile::findFirstValue($data['handle_id']);
        if (empty($user)) return false;
        $data['new_user'] = 0;
        if ((time() - $user->dateline) <= 7 * 86400) {
            $data['new_user'] = 1;
        }
        $data['role'] = $user->role;
        return XsstSessionForbiddenLog::saveRows($data);
    }

    private function _sendMessage($uid, $message = '', $from = 0)
    {
        if ($uid < 1 || !$message) return false;
        NsqClient::publish(NsqConstant::TOPIC_XS_ADMIN, array(
            'cmd'  => 'system.message',
            'data' => array(
                'from'    => $from,
                'uid'     => $uid,
                'message' => $message
            )
        ));
        return true;
    }

    private static function _translate($uid, $text, $params = [])
    {
        $uarea = XsUserSettings::findFirst($uid);
        $lang = $uarea->language ?? 'en';
        return __T($text, $params, strtolower($lang));
    }


    public function payHistory(array $params): array
    {
        $uid = intval($params['uid'] ?? 0);
        $limit = intval($params['limit'] ?? 15);
        $page = intval($params['page'] ?? 1);
        $op = trim($params['op'] ?? '');
        $reason = trim($params['reason'] ?? '');
        $startYmd = trim($params['start'] ?? '');
        $endYmd = trim($params['end'] ?? '');

        $query = [
            'order'  => 'dateline desc, id desc',
            'limit'  => $limit,
            'offset' => ($page - 1) * $limit,
        ];

        $ops = [];
        if (!empty($op)) {
            $ops[] = $op;
        }
        if ($startYmd && Helper::isDate($startYmd)) {
            $startYmd = intval(date("Ymd", strtotime($startYmd)));
        } else {
            // 默认获取前7天数据
            $startYmd = intval(date('Ymd', strtotime('-7 day')));
        }
        if ($endYmd && Helper::isDate($endYmd)) {
            $endYmd = intval(date("Ymd", strtotime($endYmd)));
        } else {
            $endYmd = intval(date('Ymd', time()));
        }

        $conditions = ['uid = ' . $uid];
        empty($ops) || ($conditions[] = "op = '{$op}'");
        empty($startYmd) || ($conditions[] = 'dateline >= ' . strtotime($startYmd));
        empty($endYmd) || ($conditions[] = 'dateline < ' . (strtotime($endYmd) + 86400));
        $conditions = implode(' and ', $conditions);
        $data = XsPayChangeNew::find(array_merge([$conditions], $query))->toArray();
        $total = XsPayChangeNew::count($conditions);
        $res = ['total' => $total, 'data' => $data];
        //'pay','consume','income','cash','income-lock','income-unlock','income-back','back'
        $op = array(
            'pay'           => '充值',
            'consume'       => '消费',
            'income'        => '收入',
            'cash'          => '提现',
            'change'        => '余额提现',
            'income-lock'   => '收入锁定',
            'income-unlock' => '收入解锁',
            'income-back'   => '退款',
            'back'          => '返还',
        );
        $reason = array(
            'order'  => '服务',
            'cash'   => '提现',
            'change' => '余额提现',
        );
        $states = array(
            'send'    => '审核中',
            'ing'     => '处理中',
            'success' => '完成',
        );

        $data = $res['data'];
        $total = intval($res['total']);
        $payIds = array();
        if (!empty($data) && is_array($data)) {
            foreach ($data as &$val) {
                $val['dateline'] = date('Y-m-d H:i:s', $val['dateline']);
                $val['reason'] = @unserialize($val['reason']) ?: @json_decode($val['reason'], true);
                $val['reason_person'] = highlight_string(var_export($val['reason'], true), true);
                $val['reason_display'] = $val['subject'];
                $val['extra'] = '';
                if (!empty($val['reason'])) {
                    $opName = $val['op'];
                    if ($opName == 'pay') {
                        $val['to'] = $val['reason']['id'];
                        $val['_sign'] = true;
                        $payIds[] = intval($val['to']);
                    } else if ($opName == 'consume') {
                        // 邀约单
                        if ($val['reason']['type'] == 'order') {
                            $rec = XsOrder::findFirst(array(
                                'conditions' => "id = :id:",
                                'bind'       => array(
                                    'id' => $val['reason']['id'],
                                ),
                            ));
                            $val['to'] = $rec->to;
                        } else if (in_array($val['reason']['type'], array('package', 'chat-gift', 'chat-coin', 'defend', 'shop-buy'))) {
                            $val['to'] = $val['reason']['to'];
                        }
                    } else if ($opName == 'income') {

                        $val['to'] = $val['reason']['from'];
                    }
                }
                $val['to'] = isset($val['to']) ? $val['to'] : ' - ';
                $val['rid'] = isset($val['reason']['rid']) ? $val['reason']['rid'] : '';
                $val['op'] = $op[$val['op']] ?? $val['op'];
            }
        }

        unset($val);
        if (!empty($payIds)) {
            $pays = XsPay::getBatchCommon($payIds, ['id', 'product_id', 'platform']);
            $iapIds = Helper::arrayFilter($pays, 'product_id');
            $iaps = XsIapConfig::getBatchCommon($iapIds, ['product_id', 'id']);

            foreach ($data as &$val) {
                if (isset($val['_sign']) && $val['_sign'] == true) {
                    $pid = intval($val['to']);
                    $payplatform = $pays[$pid]['platform'] ?? '';
                    $productId = $iaps[$pays[$pid]['product_id'] ?? 0]['product_id'] ?? '';

                    if ($payplatform == 'fomo') {
                        $tmp = explode(".", $productId);
                        array_pop($tmp);
                        $platform = implode(".", $tmp);
                        if ($platform == 'fomo') $platform = 'fomo.wechat';
                    } else {
                        $platform = $payplatform;
                    }
                    $val['extra'] = $platform;

                }
            }
        }

        //查询用户账户数据
        $profile = XsUserMoney::findFirstByUid($uid);
        if (!$profile) {
            throw new ApiException(ApiException::MSG_ERROR, '用户数据异常，请重试');
        };
        $money_available = intval($profile->money);
        $money_lock = intval($profile->money_lock);
        $money_cash = intval($profile->money_cash);

        return [
            $data, array(
                'total'             => $total,
                'money_available'   => intval($money_available),
                'money_lock'        => intval($money_lock),
                'money_cash'        => intval($money_cash),
                'all'               => 0,
                'hasError'          => false,
                'next_token_string' => empty($res['pk_next_string']) ? '' : $res['pk_next_string'],
            )
        ];
    }

    public function payUserHistory(array $params): array
    {
        $uid = intval($params['uid'] ?? 0);
        $limit = intval($params['limit'] ?? 15);
        $page = intval($params['page'] ?? 1);
        $op = trim($params['op'] ?? '');
        $reason = trim($params['reason'] ?? '');
        $startYmd = trim($params['start'] ?? '');
        $endYmd = trim($params['end'] ?? '');
        $accountType = trim($params['account_type'] ?? '');

        $sort = trim($params['sort'] ?? '');
        $dir = trim($params['dir'] ?? '');

        $query = [
            'order'  => 'id desc',
            'limit'  => $limit,
            'offset' => ($page - 1) * $limit,
        ];

        $ops = [];
        if (!empty($op)) {
            $ops[] = $op;
        }
        if ($startYmd && Helper::isDate($startYmd)) {
            $startYmd = intval(date("Ymd", strtotime($startYmd)));
        } else {
            $startYmd = intval(date('Ymd', strtotime('-7 day')));
        }
        if ($endYmd && Helper::isDate($endYmd)) {
            $endYmd = intval(date("Ymd", strtotime($endYmd)));
        } else {
            $endYmd = null;
        }

        $conditions = ['uid = ' . $uid];
        empty($ops) || ($conditions[] = "op = '{$op}'");
        $conditions[] = 'dateline >= ' . strtotime($startYmd);
        empty($endYmd) || ($conditions[] = 'dateline < ' . (strtotime($endYmd) + 86400));
        $conditions = implode(' and ', $conditions);

        $service = PayHistoryAbstractService::getHistoryType($accountType);
        $service->conditions = $conditions;
        // 增加操作排序
        if (!empty($sort) && in_array($sort, ['op', 'dateline']) && !empty($dir) && in_array($dir, array('asc', 'desc'))) {
            $query['order'] = $sort . ' ' . $dir;
        }
        $service->query = $query;
        return $service->getPayHistoryList();
    }


    public function userPunishHistory(array $params): array
    {
        $page = intval($params['page'] ?? 1);
        $limit = intval($params['limit'] ?? 15);
        $offset = ($page - 1) * $limit;

        $uid = intval($params['uid'] ?? 0);
        $type = trim($params['type'] ?? 0);

        $sort = trim($params['sort'] ?? 'id');
        $dir = trim($params['dir'] ?? 'desc');

        if (empty(XsUserProfile::findOne($uid))) {
            return ['data' => [], 'total' => 0];
        }

        $where = array();
        $where[] = "c.uid=" . $uid;

        if ($type == 4) {
            $where[] = "c.type=4";
        } else {
            $where[] = "c.type!=4";
        }

        $where = ' where ' . implode(' and ', $where);

        $conn = Di::getDefault()->getShared(XsBaseModel::SCHEMA_READ);
        $total = $conn->fetchColumn("select count(*) from xs_pay_punish_log as c {$where}");
        $data = $conn->fetchAll("select * from xs_pay_punish_log as c {$where} order by c.{$sort} {$dir} limit {$offset}, {$limit} ", \Phalcon\Db::FETCH_ASSOC);


        foreach ($data as &$v) {
            $v['dateline'] = date('Y-m-d H:i', $v['dateline']);

            $v['money'] = $v['money'] / 100;

            if ($v['admin'] > 0) {
                $v['admin'] = Helper::getAdminName(intval($v['admin']) ?? '-');
            } else {
                $v['admin'] = ' - ';
            }
        }

        return array(
            'data'  => $data,
            'total' => $total,
        );
    }

    public function userPunish(array $params): array
    {
        $admin = intval($params['uid'] ?? 0);
        $now = time();

        $type = trim($params['type'] ?? '');
        $uid = intval($params['uid'] ?? 0);
        $ac = intval($params['ac'] ?? 0);
        $money = trim($params['money'] ?? '');
        $reason = trim($params['reason'] ?? '');
        $reason2 = trim($params['reason2'] ?? '');
        $isSuper = intval($params['_super'] ?? 0);
        $mark = trim($params['mark'] ?? '');
        $frozen_type = trim($params['frozen_type'] ?? '');
        $moneyto = trim($params['moneyto'] ?? 0);
        $refundUids = trim($params['refund_uids'] ?? ''); // 退款用户


        if ($uid < 1 || $money < 0.01 || $ac < 1) {
            return [false, 'UID、操作类型、金额为必填项且金额仅支持两位小数'];
        }

        if ($ac != 3) {
            if ($money > 10000) {
                return [false, '搞什么，怎么这么多钱！你是谁？ 你在哪？ 我发现你了！！！已报警！！！'];
            }
        } else {
            if ($money > 500000) {
                return [false, '搞什么，怎么这么多钱！你是谁？ 你在哪？ 我发现你了！！！已报警！！！'];
            }
        }
        $relmoney = $money * 100;

        if (!in_array($ac, array(1, 2, 3))) {
            return [false, '操作类型错误'];
        }
        if (in_array($ac, array(1, 3)) && empty($reason2)) {
            return [false, '选择原因不能为空'];
        };
        if ($isSuper == 0 && $moneyto == 2) {
            return [false, '错误行为'];
        };

        if ($ac == 3 && $reason2 == '金额异常' && $frozen_type == 'chasingMoney' && empty($refundUids)) {
            return [false, '在原因为【金额异常】、类型为【追款】时，退款用户uid不能为空'];
        }

        // 查看xs_user_punish中是否存在uid记录
        $res = XsUserPunish::findFirst(array(
            "uid=:uid:",
            "bind" => array("uid" => $uid),
        ));
        if (!empty($res)) {
            $hasPunish = true;
        } else {
            $hasPunish = false;
        }

        //这里仅仅记录需要罚款，后台去处理实际的罚款行为
        $keyLock = 'Pay.' . $uid;
        $conn = Di::getDefault()->getShared(XsBaseModel::SCHEMA);
        $conn->begin();

        try {
            $r = Helper::fetchColumn("select get_lock('{$keyLock}', 10) ");
            if (!is_numeric($r) || $r != 1) {
                throw new \Exception("未获取到锁");
            }

            $version = 0;

            if ($moneyto == 3) {
                $sendMsgMoneyTo = "收入余额";
            } else if ($moneyto == 0) {
                $sendMsgMoneyTo = "充值余额";
            } else {
                throw new \Exception("账户类型错误");
            }

            // $user = \XsUserProfile::findFirstValue($uid, 'app_id');
            $user = XsUserProfile::findFirst($uid);

            $punish_type = '钻石';
            $pay_type = '钻石';
            $income_type = '魅力值';

            $msgMoney = $money * 100;

            $sendMsgMoneyTo = $this->_translate($uid, $sendMsgMoneyTo);
            if ($ac == 1) {
                $conn->execute("update xs_user_money set money_debts = money_debts + {$relmoney} where uid = {$uid}");
                if ($hasPunish) {
                    $conn->execute("update xs_user_punish set type = 0 where uid = {$uid}");
                } else {
                    $conn->execute("insert ignore into xs_user_punish (uid, money) values ({$uid}, 0)");
                }
                $str = "官方罚款%s" . $punish_type . "通知";
                $str = $this->_translate($uid, $str);
                $str = sprintf($str, $msgMoney);
                XsPayChangeNew::log($uid, 0, 'punish', $str, array(
                    'type' => 'punish',
                    'id'   => $uid
                ));
                $reason = "【罚款处罚】该账号由于【%s】，被官方处以罚款%s" . $punish_type . "的处罚，系统将会从您的账户余额自动扣取罚金。如对该处罚有异议，可联系客服进行申诉。";
                $reason2 = $this->_translate($uid, $reason2);
                $reason = $this->_translate($uid, $reason);
                $reason = sprintf($reason, $reason2, $msgMoney);
            } else if ($ac == 3) {
                // 官方冻结 同罚款逻辑一致 通过xs_user_punish的type区分
                $conn->execute("update xs_user_money set money_debts = money_debts + {$relmoney} where uid = {$uid}");
                if ($hasPunish) {
                    $conn->execute("update xs_user_punish set type = 1 where uid = {$uid}");
                } else {
                    $conn->execute("insert ignore into xs_user_punish (uid, money, `type`) values ({$uid}, 0, 1)");
                }
                $str = "官方冻结%s" . $punish_type . "通知";
                $str = $this->_translate($uid, $str);
                $str = sprintf($str, $msgMoney);
                XsPayChangeNew::log($uid, 0, 'punish', $str, array(
                    'type' => 'freeze_punish',
                    'id'   => $uid
                ));
                if ($reason2 == '金额异常' && in_array($frozen_type, ['cancelOrder', 'chasingMoney'])) {
                    if ($frozen_type == 'chasingMoney') { // 追款
                        $refundUids = str_replace('，', ',', $refundUids); // 如果有中文逗号，统一替换成英文逗号
                        // 中东大区 币*100
                        $reason = $this->_translate($uid, '冻结罚款并且金额异常的追款类型', ['money' => $msgMoney, 'refundUid' => $refundUids]);
                    } else { // 撤单
                        // 中东大区 币*100
                        $reason = $this->_translate($uid, '冻结罚款并且金额异常的撤单类型', ['money' => $msgMoney]);
                    }
                } else {
                    $reason = "【冻结通知】该账号由于【%s】，被官方处以冻结%s" . $punish_type . "的处理，系统将会从您账户余额自动执行金额冻结。如对该处罚有异议，可联系客服进行申诉。";
                    $reason2 = $this->_translate($uid, $reason2);
                    $reason = $this->_translate($uid, $reason);
                    // 中东大区 币*100
                    $reason = sprintf($reason, $reason2, $msgMoney);
                }
            } else if ($isSuper == 0) {
                $punishProcess = XsUserPunish::findFirst($uid);
                if (!$punishProcess) throw new \Exception('该用户未被处罚过，无法退款');

                $punishWait = XsUserMoney::useMaster()->findFirst($uid);
                if ($punishWait->money_debts + $punishProcess->money < $relmoney) {
                    throw new \Exception('错误：退款金额无效');
                }

                $moneyFromDebts = 0;
                $moneyFromCash = 0;

                $_in_a = 0;
                $_in_c = 0;
                $_in_o = 0;
                $_in_c_b = 0;

                //退款，先执行待执行金额，再执行已执行的
                if ($punishWait->money_debts >= $relmoney) {
                    //待处罚金额 比 退款的还多，直接减少待处罚金额
                    $moneyFromDebts = $relmoney;
                    $conn->execute("update xs_user_money set money_debts = money_debts - {$relmoney} where uid = {$uid}");
                } else {
                    $moneyFromDebts = intval($punishWait->money_debts);
                    $moneyFromCash = $relmoney - $moneyFromDebts;
                    if ($moneyto == 1) {
                        $_in_c = $moneyFromCash;
                        $conn->execute("update xs_user_money set money_cash = money_cash + {$moneyFromCash}, money_debts = money_debts - {$punishWait->money_debts} where uid = {$uid}");
                    } else if ($moneyto == 2) {
                        throw new \Exception('错误：不可操作');
                    } else if ($moneyto == 3) {
                        $_in_c_b = $moneyFromCash;
                        $conn->execute("update xs_user_money set money_cash_b = money_cash_b + {$moneyFromCash}, money_debts = money_debts - {$punishWait->money_debts} where uid = {$uid}");
                    } else {
                        $_in_a = $moneyFromCash;
                        $conn->execute("update xs_user_money set money = money + {$moneyFromCash}, money_debts = money_debts - {$punishWait->money_debts} where uid = {$uid}");
                    }
                    $conn->execute("update xs_user_punish set money = money - {$moneyFromCash} where uid = {$uid}");
                }
                $str = "官方退还罚款%s" . $punish_type;
                $str = $this->_translate($uid, $str);
                $str = sprintf($str, $msgMoney);
                XsPayChangeNew::log($uid, $relmoney, 'punish-back', $str, array(
                    'type'    => 'punish-back',
                    'id'      => $uid,
                    'moneyto' => $moneyto,
                    '_in_a'   => intval($_in_a),
                    '_in_c'   => intval($_in_c),
                    '_in_o'   => intval($_in_o),
                    '_in_c_b' => intval($_in_c_b)
                ));

                $reason = "【官方赠送金额】官方赠送%s" . $punish_type . "，其中填补欠款%s" . $punish_type . "，增加%s%s" . $punish_type . "，请在我->钱包查收。";
                $reason = $this->_translate($uid, $reason);
                $reason = sprintf($reason, $relmoney, $moneyFromDebts, $sendMsgMoneyTo, $moneyFromCash);
            } else if ($isSuper == 1) {
                $version = 1;
                $reason = "【官方赠送金额】官方赠送%s" . $pay_type . "，增加%s%s" . $pay_type . "，请在我->钱包查收。";
                $reason = $this->_translate($uid, $reason);
                $reason = sprintf($reason, $relmoney, $sendMsgMoneyTo, $relmoney);
                if ($moneyto == 3) {
                    $reason = "【官方赠送】官方赠送" . $income_type . "%s，增加" . $income_type . "余额%s。";
                    $reason = $this->_translate($uid, $reason);
                    $reason = sprintf($reason, $relmoney, $relmoney);
                }
            }


            $rec = XsPayPunishLog::useMaster();
            $rec->uid = $uid;
            $rec->app_id = $user->app_id ?? 9;
            $rec->type = $ac;
            $rec->money = $relmoney;
            $rec->admin = $admin;
            $rec->reason = $reason;
            $rec->mark = $mark;
            $rec->dateline = $now;
            $rec->version = $version;
            $rec->state = 0;
            $rec->vtype = $moneyto;
            $rec->frozen_type = $frozen_type;
            $rec->save();

            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            Helper::fetchColumn("select release_lock('{$keyLock}') ");
            return [false, "请求(" . $e->getMessage() . ")，请重试"];
        }
        Helper::fetchColumn("select release_lock('{$keyLock}') ");

        // 发送消息
        if ($reason && $version < 1) {
            $this->_sendMessage($uid, $reason);
        }

        //执行罚款
        NsqClient::publish(NsqConstant::TOPIC_XS_CMD, array(
            'cmd'  => 'check.punish',
            'data' => array(
                'uids' => $uid,
            )
        ));

        /*【越南】
        【罚款、冻结、指定罚款 】
        发送通知*/
        $userArea = XsUserBigarea::findFirst($uid);
        if (in_array($ac, [1, 3, 4,]) && $userArea && $userArea->bigarea_id == 7) {
            $headmsg = XsPayPunishLog::TYPE_MAP[$ac]['text'];
            $moneyDebts = 0;    //log表本函数未插入数据默认0
            $tmpM = XsUserMoney::useMaster()::findFirst($uid);
            if ($tmpM) $moneyDebts = ($tmpM->money_debts) / 100;
            $dateline = ($now > 0) ? date('Y-m-d H:i', $now) : '-';
            $bodymsg = $uid . '，' . ($user->name ?? '-') . '，' . ($headmsg) . '' . ($relmoney / 100) . '币，当前欠款金额' . $moneyDebts . '元，' . $mark . '，' . $dateline;

            $obj = factory_single_obj(SdkSlack::class);
            $obj->sendMsg((ENV == 'dev') ? '' : '',
                'text', '【' . $headmsg . '】' . $bodymsg);
        }

        return [true, ''];
    }


    public function accountPunish(array $params): array
    {
        return UserMoneyService::getInstance()->accountPunish($params, intval($params['admin_id'] ?? 0));
    }

    public function userSex(array $params): array
    {
        $uid = intval($params['uid'] ?? 0);
        $sex = intval($params['sex'] ?? 0);

        $user = XsUserProfile::findOne($uid);
        if (empty($user)) {
            return [false, '用户不存在'];
        }

        if (in_array($sex, array(1, 2)) && $user['sex'] != $sex) {
            return XsUserProfile::edit($uid, ['sex' => $sex]);
        }
        return [false, '不需要修改'];
    }
    public function userNameModify(array $params): array
    {
        $uid = intval($params['uid'] ?? 0);
        $tp = trim($params['tp'] ?? '');
        $ac = intval($params['ac'] ?? 0);
        $reason = trim($params['reason'] ?? '');
        $admin = intval($params['admin_id'] ?? 0);
        $now = time();

        $user = XsUserProfile::findOne($uid);
        if (empty($user)) {
            return [false, '用户不存在'];
        }


        $tpdata = XsUserModify::getValueByKey($uid, "name", 0);
        if ($tpdata) {
            $tpdata->state = 3;
            $tpdata->admin_id = $admin;
            $tpdata->modify_time = $now;
            $tpdata->update_time = $now;
            $tpdata->save();

            XsUserModify::updateUnUse($uid, "name");
        }

        XsUserProfile::edit($uid, ['name' => Helper::translate($uid, "无名氏")]);


        if ($reason) {
            $message = self::_translate($uid, $reason);
            self::sendMsgByVerify($uid, $message);
            //self::sendMsgByVerify($uid, $data['re']);
        }

        // 所有用户-昵称/签名-置为无效-原因（含有色情/涉政/违法/第三方联系方式）
        if (
            strpos($reason, '含有色情信息') > 0 ||
            strpos($reason, '含有涉政信息') > 0 ||
            strpos($reason, '含有违法信息') > 0 ||
            strpos($reason, '含有第三方联系方式') > 0
        ) {
            self::setUserInReview($uid);
        }

        return [true, ''];
    }

    public function userSignModify(array $params): array
    {
        $uid = intval($params['uid'] ?? 0);
        $tp = trim($params['tp'] ?? '');
        $ac = intval($params['ac'] ?? 0);
        $reason = trim($params['reason'] ?? '');
        $admin = intval($params['admin_id'] ?? 0);
        $now = time();

        $profile = XsUserProfile::findFirstValue($uid);
        if (!$profile) {
            return [false, '用户不存在'];
        };

        $tpdata = XsUserModify::getValueByKey($uid, "sign", 0);
        if ($tpdata) {
            $tpdata->state = 3;
            $tpdata->admin_id = $admin;
            $tpdata->modify_time = $now;
            $tpdata->update_time = $now;
            $tpdata->save();

            XsUserModify::updateUnUse($uid, "sign");
        }

        $profile->sign = "";
        $profile->save();

        if ($reason) {
            $message = self::_translate($uid,$reason);
            self::sendMsgByVerify($uid, $message);
        }

        // 所有用户-昵称/签名-置为无效-原因（含有色情/涉政/违法/第三方联系方式）
        if (
            strpos($reason, '含有色情信息') > 0 ||
            strpos($reason, '含有涉政信息') > 0 ||
            strpos($reason, '含有违法信息') > 0 ||
            strpos($reason, '含有第三方联系方式') > 0
        ) {
            self::setUserInReview($uid);
        }

        return [true, ''];
    }

    public static function sendMsgByVerify($uid, $message, $from = 0)
    {
        if (ENV == 'dev') return false;
        if ($uid < 1 || !$message) return false;
        $from = $from > 0 ? $from : 10000000; // 系统通知uid
        NsqClient::publish(NsqConstant::TOPIC_XS_CMD, array(
                'cmd'  => 'live.message',
                'data' => array(
                    'from'    => $from,
                    'to'      => $uid,
                    'message' => $message,
                    'extra'   => null,
                )
            )
        );
    }

    public static function setUserInReview($uid)
    {
        $now = time();
        $dateline = strtotime(date("2030-01-01"));

        $hasNotice = false;

        // 1 昵称，2 签名，3头像
        foreach (array(1, 2, 3) as $type) {
            $hasReviewmodUpdate = false;
            $hasReviewmod = XsUserReviewmod::findFirst(array("uid=:uid: and type=:type:", "bind" => array("uid" => $uid, 'type' => $type)));
            if ($hasReviewmod) {
                if ($hasReviewmod->dateline < $dateline) {
                    $hasReviewmod->dateline = $dateline;
                    $hasReviewmod->save();
                    $hasReviewmodUpdate = true;
                }
            } else {
                $hasReviewmod = new XsUserReviewmod();
                $hasReviewmod->uid = $uid;
                $hasReviewmod->type = $type;
                $hasReviewmod->dateline = $dateline;
                $hasReviewmod->save();
                $hasReviewmodUpdate = true;
            }

            if ($hasReviewmodUpdate) {
                $rec = new XsUserReviewmodlog();
                $rec->uid = $uid;
                $rec->type = $type;
                $rec->dateline = $dateline;
                $rec->admin = 1;
                $rec->update_time = $now;
                $rec->duration = 315360000;
                $rec->reason = '系统自动';
                $rec->save();

                $hasNotice = true;
            }
        }

        if ($hasNotice) {
            $message = self::_translate($uid, '由于您提交的头像/形象照/昵称/签名违规，您之后提交的个人资料都将被审核通过后才能生效');
            self::sendMsgByVerify($uid, $message);
        }

        return true;
    }

    public function userValidReviewMod(array $params): array
    {
        $now = time();
        $adminId = intval($params['admin_id'] ?? 0);
        $uid = intval($params['uid'] ?? 0);
        $time = intval($params['time'] ?? 0);
        $ac = intval($params['ac'] ?? 0);
        $reason = trim($params['reason'] ?? '');

        if ($time < 1 || $ac < 1) {
            return [false, '参数错误'];
        };

        $modifyData = XsUserReviewmod::findOneByWhere([['uid', '=', $uid], ['type', '=', $ac]]);;

        $hasUpdate = false;

        $duration = $time * 86400;
        $dateline = $now + $duration;

        // 恢复
        if ($time == 999) {
            if (!empty($modifyData)) {
                XsUserReviewmod::deleteById($modifyData['id']);
                $hasUpdate = true;
            }
        } else {
            if (!empty($modifyData)) {
                XsUserReviewmod::edit($modifyData['id'], ['dateline' => $dateline]);
            } else {
                XsUserReviewmod::add([
                    'uid'      => $uid,
                    'type'     => $ac,
                    'dateline' => $dateline
                ]);
            }
            $hasUpdate = true;
        }

        if (!$hasUpdate) {
            return [true, ''];
        };

        XsUserReviewmodlog::add(
            [
                'uid'         => $uid,
                'type'        => $ac,
                'dateline'    => $dateline,
                'admin'       => $adminId,
                'update_time' => $now,
                'duration'    => $duration,
                'reason'      => $reason
            ]
        );


        // 发送消息
        if ($reason) {
            $reason = '您因多次提交违规%s，被处以限制修改%s天的处罚，%s天内修改的%s均须先通过审核才能显示';
            if ($ac == 1) $typestr = '昵称';
            if ($ac == 2) $typestr = '签名';
            if ($ac == 3) $typestr = '头像';
            $typestr = $this->_translate($uid, $typestr);
            $reason = $this->_translate($uid, $reason);

            // 英文的翻译 和 中文的%s 和 个数对不上
            $uarea = XsUserSettings::findFirst($uid);
            if ($uarea) {
                $lang = $uarea->language;
            } else {
                $lang = '';
            }
            if ($lang == 'en') {
                $message = sprintf($reason, $typestr, $time, $typestr, $typestr, $time);
            } elseif ($lang == 'ms') {
                // 马来语
                $message = sprintf($reason, $typestr, $time, $typestr, $time, $typestr);
            } else {
                $message = sprintf($reason, $typestr, $time, $time, $typestr);
            }
            $this->_sendMessage($uid, $message);
        }
        return [true, ''];
    }

    public function userValidForbidden(array $params): array
    {
        $uid = intval($params['uid'] ?? 0);
        $time = intval($params['time'] ?? 0);
        $ac = intval($params['ac'] ?? 0);
        $reason = trim($params['reason'] ?? '');
        $adminId = intval($params['admin_id'] ?? 0);

        if ($time < 1 || $ac < 1) {
            return [false, '参数错误'];
        }

        $user = XsUserProfile::findOne($uid);
        if (empty($user)) {
            return [false, '用户不存在'];
        }

        $modifyData = XsUserForbiddenModify::findOneByWhere([['uid', '=', $uid], ['type', '=', $ac]]);


        $hasUpdate = false;

        $duration = $time * 86400;
        $dateline = time() + $duration;

        // 恢复
        if ($time == 999) {
            if (!empty($modifyData)) {
                XsUserForbiddenModify::deleteById($modifyData['id']);
                $hasUpdate = true;
            }
        } else {
            if (!empty($modifyData)) {
                XsUserForbiddenModify::edit($modifyData['id'], ['dateline' => $dateline]);
            } else {
                XsUserForbiddenModify::add([
                    'uid'      => $uid,
                    'type'     => $ac,
                    'dateline' => $dateline,
                ]);
            }
            $hasUpdate = true;
        }

        if (!$hasUpdate) {
            return [true, ''];
        }
        XsUserForbiddenModifylog::add([
            'uid'         => $uid,
            'type'        => $ac,
            'dateline'    => $dateline,
            'admin'       => $adminId,
            'update_time' => time(),
            'duration'    => $duration,
            'reason'      => $reason,
        ]);
        // 发送消息
        if ($reason) {
            $typestr = '昵称';
            if ($ac == 2) $typestr = '签名';
            $typestr = $this->_translate($uid, $typestr);
            if ($time == 999) {
                $reason = $this->_translate($uid, '您修改%s的权限已恢复');
                $reason = sprintf($reason, $typestr);
            } else {
                $reason = $this->_translate($uid, '您提交的%s违规，%s天内禁止修改');
                $reason = sprintf($reason, $typestr, $time);
            }
            $this->_sendMessage($uid, $reason);
        }

        return [true, ''];
    }

    public function getUserReviewModLog(array $params): array
    {
        $page = intval($params['page'] ?? 1);
        $limit = intval($params['limit'] ?? 15);

        $uid = intval($params['uid'] ?? 0);

        $query = [['type', 'IN', [1, 2, 3, 4]]];
        $uid && $query[] = ['uid', '=', $uid];

        $data = XsUserReviewmodlog::getListAndTotal($query, '*', 'update_time desc', $page, $limit);

        foreach ($data['data'] as &$rec) {
            $updateTime = $rec['update_time'] ?? 0;
            $dateline = $rec['dateline'] ?? 0;
            $duration = $rec['duration'] ?? 0;

            $rec['dateline'] = $dateline ? date('Y-m-d H:i:s', $dateline) : '';
            $rec['update_time'] = $updateTime ? date('Y-m-d H:i:s', $updateTime) : '';
            $rec['admin'] = Helper::getAdminName($rec['admin'] ?? '');

            if ($duration >= 86400) {
                $rec['duration'] = $duration / 86400;
            }

            if ($duration >= 86400) {
                if ($duration / 86400 == 999) {
                    $rec['duration'] = '恢复';
                } else {
                    $rec['duration'] = $duration / 86400 . '天';
                }
            } else {
                $rec['duration'] = $duration . '秒';
            }
        }

        return $data;
    }

}
