<?php

namespace Imee\Models\Xs;

use \Imee\Comp\Common\Orm\Traits\MysqlCollectionTrait;
use Imee\Comp\Gid\Models\Xsst\XsstGidRelationshipGroup;
use Imee\Comp\Operate\Auth\Models\Cms\CmsModuleUser;
use Imee\Exception\ApiException;
use Imee\Models\Es\EsXsUserProfile;
use Imee\Service\Helper;
use Imee\Service\Operate\User\UserPriceLevelService;

class XsUserProfile extends BaseModel
{
	public static $primaryKey = 'uid';

    public const DELETE_NORMAL = 0;
    public const DELETE_CANNOT_SEARCH = 1;
    public const DELETE_FORBID_CHAT = 2;
    public const DELETE_FORBID_LOGIN = 3;

	public const SEX_MALE = 1;
	public const SEX_FEMALE = 2;

    const VIP_LEVEL_ONE = 1;
    const VIP_LEVEL_TWO = 2;
    const VIP_LEVEL_THREE = 3;
    const VIP_LEVEL_FOUR = 4;
    const VIP_LEVEL_FIVE = 5;
    const VIP_LEVEL_SIX = 6;
    const VIP_LEVEL_SEVEN = 7;
    const VIP_LEVEL_EIGHT = 8;

    public static $vipLevelMap = [
        self::VIP_LEVEL_ONE   => 'VIP1',
        self::VIP_LEVEL_TWO   => 'VIP2',
        self::VIP_LEVEL_THREE => 'VIP3',
        self::VIP_LEVEL_FOUR  => 'VIP4',
        self::VIP_LEVEL_FIVE  => 'VIP5',
        self::VIP_LEVEL_SIX   => 'VIP6',
        self::VIP_LEVEL_SEVEN => 'VIP7',
        self::VIP_LEVEL_EIGHT => 'VIP8',
    ];

    const VIP_DAYS7 = 7;
    const VIP_DAYS = 30;

    public static $vipDaysMap = [
        self::VIP_DAYS7 => '7天',
        self::VIP_DAYS  => '30天',
    ];

    public static $deleted_arr = [
        self::DELETE_NORMAL => '正常',
        self::DELETE_CANNOT_SEARCH => '不可被搜索到',
        self::DELETE_FORBID_CHAT => '不可被搜索到且禁止聊天',
        self::DELETE_FORBID_LOGIN => '不可被搜索到且禁止登录',
    ];

    public static $reasonArr = [
        1 => '相同安全手机号',
        2 => '相同支付账号',
        3 => '相同设备（注册-注册）',
        4 => '相同设备（注册-使用）',
        5 => '相同设备（使用-注册）',
        6 => '相同设备（使用-使用）'
    ];

	public static $sex_arr = [
		self::SEX_MALE => '男',
		self::SEX_FEMALE => '女',
	];

    private static $_levelsNew = array(
        1,
        10, 50,
        100, 300, 700,
        1000, 1500, 2000, 3000, 5000, 7000,
        10000, 15000, 20000, 25000, 30000, 40000, 50000, 60000, 80000,
        100000, 120000, 140000, 160000, 180000, 200000,
        230000, 260000, 290000,
        320000, 370000,
        420000, 470000,
        520000, 570000,
        650000, 730000, 810000, 910000,
        1010000, 1110000, 1210000, 1360000,
        1510000, 1660000, 1810000,
        2010000, 2210000, 2410000, 2610000, 2910000,
        3210000, 3510000, 3810000,
        4310000, 4810000, 5310000, 5810000,
        6610000, 7410000, 8210000, 9010000,
        9810000, 11010000, 12210000, 13410000,
        14610000, 15810000, 17610000, 19410000,
        21210000, 23010000, 24810000, 27410000,
        30010000, 32610000, 35210000, 37810000,
        41810000, 45810000, 49810000, 53810000,
        57810000, 65810000, 73810000, 81810000,
        89810000, 97810000, 112810000, 127810000,
        142810000, 157810000, 172810000, 197810000,
        222810000, 247810000, 272810000,
        297810000, 347810000
    );

	const ONLINE_STATUS_ON = 0;
	const ONLINE_STATUS_OFF = 1;
	const ONLINE_STATUS_QUIT = 2;

	public static $onlineStatusArr = [
		self::ONLINE_STATUS_ON   => '在线',
		self::ONLINE_STATUS_OFF  => '离线',
		self::ONLINE_STATUS_QUIT => '退出',
	];

    /**
     * @param $level
     * @return int[]
     */
    public static function getLevelMoney($level)
    {
        $data = array("min" => 0, "max" => 10000000000);
        if ($level < 1) {
            return $data;
        }

        $count = count(self::$_levelsNew);
        if ($level > $count) {
            $data['min'] = self::$_levelsNew[$count - 1] * 100;
            return $data;
        }

        $data['min'] = self::$_levelsNew[$level - 1] * 100;
        $data['max'] = self::$_levelsNew[$level] * 100;
        return $data;
    }

    public static function findFirstValue($uid, $columns = '*')
    {
        return static::findFirst(array(
            'conditions' => "uid = :uid:",
            'bind' => array(
                'uid' => $uid,
            ),
            'columns' => $columns,
        ));
    }

    /**
     * 根据uid批量获取用户名等信息
     * @param array $uidArr uid
     * @param array $fieldArr 查询的字段
     * @param mix $columns
     * @return array
     */
    public static function getUserProfileBatch($uidArr = [], $fieldArr = ['uid', 'name'], $columns = null)
    {
        if (empty($uidArr)) {
            return [];
        }
        if (!in_array('uid', $fieldArr)) {
            $fieldArr[] = 'uid';
        }
        $data = self::find(array(
            'columns' => implode(',', $fieldArr),
            'conditions' => "uid in ({uid:array})",
            'bind' => array(
                'uid' => $uidArr,
            ),
        ))->toArray();
        if (empty($data)) {
            return array();
        }

        return array_column($data, $columns, 'uid');
    }

    public static function getUserSex($uid)
    {
        $user = self::findFirst([
            'conditions' => "uid = :uid:",
            'bind' => array(
                'uid' => $uid,
            )
        ]);
        return $user ? $user->sex : self::SEX_MALE;
    }

    public static function getJoinCount(array $condition, array $joinCondition, string $bigAreaId = '')
    {
        $modelsManager = self::modelsManager();
        $builder = $modelsManager->createBuilder()
            ->columns('count(*) as cnt')
            ->addfrom(self::class, 'u');
        foreach ($joinCondition as $join) {
            $builder->leftjoin($join['class'], $join['condition'], $join['table']);
        }
        list($builder, $_) = self::parseCondition($builder, $condition);
        if (empty($bigAreaId)) {
            $total = $builder->getQuery()->execute()->toArray();
        } else {
            $total = $builder->andWhere(sprintf("(b.bigarea_id IN (%s) OR b.bigarea_id IS NULL)", $bigAreaId))
                ->getQuery()->execute()->toArray();
        }
        return $total[0]['cnt'] ?? 0;
    }

    public static function getListJoinTable(array $condition, array $joinCondition, array $columns, string $order = '', int $page = 0, int $pageSize = 0,string $bigAreaId = '')
    {
        try {
            $total = self::getJoinCount($condition, $joinCondition ,$bigAreaId);
            if ($total == 0) {
                return ['data' => [], 'total' => 0];
            }
            $modelsManager = self::modelsManager();
            $builder = $modelsManager->createBuilder()
                ->columns($columns)
                ->addfrom(self::class, 'u');
            foreach ($joinCondition as $join) {
                $builder->leftjoin($join['class'], $join['condition'], $join['table']);
            }
            list($builder, $_) = self::parseCondition($builder, $condition);
            if (!empty($order)) {
                $builder->orderBy($order);
            }
            if ($page && $pageSize) {
                $startLimit = ($page - 1) * $pageSize;
                $builder->limit($pageSize, $startLimit);
            }
            if (empty($bigAreaId)) {
                $data = $builder->getQuery()->execute()->toArray();
            } else {
                $data = $builder->andWhere(sprintf("(b.bigarea_id IN (%s) OR b.bigarea_id IS NULL)", $bigAreaId))
                    ->getQuery()->execute()->toArray();
            }
            return ['data' => $data, 'total' => $total];
        } catch (\Exception $e) {
            throw new ApiException(ApiException::MSG_ERROR,$e->getMessage());
        }
    }

    public static function checkUid(array $uids)
    {
        $absent = [];
        foreach (array_chunk($uids, 500) as $item) {
            $list = self::getListByWhere([
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

    //获取gid下大号uid 此处按vip等级最高
    public static function getBigUid($gid)
    {
        if(empty($gid)){
            return 0;
        }
        //获取gid下所有uid
        $allUid = XsstGidRelationshipGroup::getGidsUser($gid);
        if(empty($allUid)){
            return 0;
        }
        $allUidInfo = self::getUserProfileBatch($allUid,['uid','pay_room_money']);
        $bigUid = 0;
        //vip等级最高的就是pay_room_money最大的
        $maxPayRoomMoney = 0;
        foreach($allUidInfo as $item){
            if(empty($maxPayRoomMoney)){
                $maxPayRoomMoney = $item['pay_room_money'];
                $bigUid = $item['uid'];
            }elseif(!empty($maxPayRoomMoney) && $item['pay_room_money'] > $maxPayRoomMoney){
                $maxPayRoomMoney = $item['pay_room_money'];
                $bigUid = $item['uid'];
            }
        }
        return $bigUid;
    }

    //获取gid下关联账号最后一次在线
    public static function getGidLastOnline($gid)
    {
        if(empty($gid)){
            return 0;
        }
        //获取gid下所有uid
        $allUid = XsstGidRelationshipGroup::getGidsUser($gid);
        if(empty($allUid)){
            return 0;
        }
        $allUid = array_column($allUid,'uid');
        $allUidInfo = self::getUserProfileBatch($allUid,['uid','online_dateline']);
        $onlineDateline = 0;
        foreach($allUidInfo as $item){
            if(empty($onlineDateline)){
                $onlineDateline = $item['online_dateline'];
            }elseif(!empty($onlineDateline) && $item['online_dateline'] > $onlineDateline){
                $onlineDateline = $item['online_dateline'];
            }
        }
        return $onlineDateline;
    }

    public static function getGidSmallByUid($uid)
    {
        $gid_user = XsstGidRelationshipGroup::getUidGroupUser($uid);
        if (empty($gid_user)) return [$uid];
        return array_column($gid_user, 'uid');
    }

    /**
     * 根据gid获取uids
     * @param $gid
     * @return array
     */
    public static function getSmallByGid($gid)
    {
        $list = XsstGidRelationshipGroup::getGidsUser($gid);
        return array_column($list, 'gid', 'uid');
    }

    /**
     * 查询
     */
    public static function queryUsersEs($filter = [], $page = 0, $limit = 0, $sort = [], $extra = []): array
    {
        $es = new EsXsUserProfile();
        $options = [];

        $start = trim($filter['start']);
        $end = trim($filter['end']);
        $online_start = trim($filter['online_start']);
        $online_end = trim($filter['online_end']);
        $channel = trim($filter['channel']);
        $svip = intval($filter['svip']);
        $evip = intval($filter['evip']);
        $app_id = intval($filter['app_id']);
        $lang = $filter['language'] ? $filter['language'] : '';
        $titleNew = $filter['title_new'] ? $filter['title_new'] : '';
        $uids = empty($filter['uid']) ? [] : (is_numeric($filter['uid']) ? [$filter['uid']] : explode(',', $filter['uid']));
        $now = time();

        if (isset($filter['deleted']) && is_numeric($filter['deleted']) && in_array($filter['deleted'], [-1, 0, 1, 2, 3])) {
            $options['bool']['must'][] = ['term' => ['deleted' => intval($filter['deleted'])]];
        }
        if (isset($filter['role']) && is_numeric($filter['role']) && in_array($filter['role'], [0, 1, 2, 3])) {
            $options['bool']['must'][] = ['term' => ['role' => intval($filter['role'])]];
        }

        // 单个uid查询
        if (count($uids) == 1) {
            $options['bool']['must'][] = ['term' => ['uid' => intval($filter['uid'])]];
        }
        // 多个uid查询
        if (count($uids) > 1) {
            $options['bool']['must'][] = ['terms' => ['uid' => $uids]];
        }

        if (isset($filter['name']) && !empty($filter['name'])) {
            $options['bool']['must'][] = ['match_phrase' => ['name' => $filter['name']]];
        }
        if (isset($filter['sign']) && !empty($filter['sign'])) {
            $options['bool']['must'][] = ['match_phrase' => ['sign' => $filter['sign']]];
        }
        if (isset($filter['sex']) && is_numeric($filter['sex']) && $filter['sex'] > 0) {
            $options['bool']['must'][] = ['term' => ['sex' => intval($filter['sex'])]];
        }

        if (Helper::isDate($start)) {
            $options['bool']['must'][] = ['range' => ['dateline' => ['gte' => strtotime($start)]]];
        }

        if (Helper::isDate($end)) {
            $options['bool']['must'][] = ['range' => ['dateline' => ['lte' => strtotime($end) + 86400]]];
        }

        if (Helper::isDate($online_start)) {
            $options['bool']['must'][] = ['range' => ['online_dateline' => ['gte' => strtotime($online_start)]]];
        }

        if (Helper::isDate($online_end)) {
            $options['bool']['must'][] = ['range' => ['online_dateline' => ['lte' => strtotime($online_end) + 86400]]];
        }

        if (!empty($channel)) {
            $options['bool']['must'][] = ['term' => ['channel' => $channel]];
        }

        if ($svip > 0) {
            $money = XsUserProfile::getLevelMoney((int)$svip);
            $options['bool']['must'][] = ['range' => ['pay_room_money' => ['gte' => $money['min']]]];
        }

        if ($evip > 0) {
            $money = XsUserProfile::getLevelMoney((int)$evip);
            $options['bool']['must'][] = ['range' => ['pay_room_money' => ['lte' => $money['max']]]];
        }

        if ($app_id > 0) {
            $options['bool']['must'][] = ['term' => ['app_id' => intval($app_id)]];
        }

        $language = $extra['sessionLanguage'];
        //		$langKey    = array_keys(Helper::getLanguageArr());
        //		$language[] = ''; // 默认带出语音为空的数据

        if (!empty($lang)) {
            if (!in_array($lang, $language)) {
                return ['results' => [], 'total' => 0];
            }
            $language = [$lang];
            $options['bool']['must'][] = ['terms' => ['language' => $language]];
        }

        $options['bool']['should'][]['bool']['must_not'] = ['wildcard' => ['language' => '*']]; // 为了查询出不存在language字段的数据
        $options['bool']['should'][]['bool']['must'] = ['terms' => ['language' => $language]];
        //		$options['bool']['must'][] = ['terms' => ['language.keyword' => $language]];

        if (!empty($titleNew)) {
            // 爵位对应的es字段是level
            $options['bool']['must'][] = ['term' => ['level' => $titleNew]];
        }

        if (empty($options)) {
            $options['bool']['must'][] = ['range' => ['online_dateline' => ['gte' => strtotime(date('Y-m-d'))]]];
        }

        $sort['sort'] = $sort['sort'] ?? 'uid';
        $sort['dir'] = $sort['dir'] ?? 'desc';

        $list = $es::searchByQuery($options, null, null, $limit, ($page - 1) * $limit, [$sort['sort'] => $sort['dir']]);
        $total = $list->totalHits();
        $res = $list->toArray();

        if ($total == 0) {
            return ['results' => $res, 'total' => 0];
        }

        $uids = array_column($res, 'uid');

        $user = XsUserProfile::query()
            ->where('uid IN ({uid:array})', ['uid' => $uids])
            ->execute()
            ->toArray();
        $user = array_column($user, null, 'uid');

        // 查询用户版本
        $userVersions = XsUserVersion::query()
            ->where('uid IN ({uid:array})', ['uid' => $uids])
            ->execute()
            ->toArray();
        $userVersions = array_column($userVersions, null, 'uid');

        // 用户爵位表
        $userTitleNew = XsUserTitleNew::query()
            ->columns('uid,level,subscribe_time')
            ->where('uid IN ({uid:array})', ['uid' => $uids])
            ->execute()
            ->toArray();
        $userTitleNew = array_column($userTitleNew, null, 'uid');

        // 用户语言
        $userLanguage = XsUserSettings::query()
            ->columns('uid,language')
            ->where('uid IN ({uid:array})', ['uid' => $uids])
            ->execute()
            ->toArray();
        $userLanguage = array_column($userLanguage, null, 'uid');

        $uids = array_column($res, 'uid');
        $priceLevel = UserPriceLevelService::getInstance()->getList($uids);
        foreach ($res as &$rec) {
            // 合并用户关键信息
            $rec = isset($user[$rec['uid']]) ? array_merge($rec, $user[$rec['uid']]) : $rec;
            // 合并版本信息
            if (isset($userVersions[$rec['uid']])) {
                $rec['version'] = $userVersions[$rec['uid']]['version'];
                $rec['platform'] = $userVersions[$rec['uid']]['platform'];
                $rec['jversion'] = $userVersions[$rec['uid']]['jversion'];
                $rec['channel'] = $userVersions[$rec['uid']]['channel'];
                $rec['mac'] = $userVersions[$rec['uid']]['mac'];
            } else {
                $rec['version'] = '';
            }

            $rec['ymd'] = $rec['dateline'] > 0 ? date('Ymd', $rec['dateline']) : '';
            $rec['dateline'] = $rec['dateline'] > 0 ? date('Y-m-d H:i', $rec['dateline']) : ' - ';
            $rec['birthday'] = Helper::formatBirthday($rec['birthday']);
            $rec['online_dateline'] = $rec['online_dateline'] > 0 ? date('Y-m-d H:i', $rec['online_dateline']) : ' - ';
            $rec['app_name'] = isset($rec['app_id']) ? Helper::getAppName($rec['app_id']) : 'Partying';
            if (!empty($rec['icon'])) $rec['icon'] = PARTYING_OSS . $rec['icon'];
            if (!empty($rec['video'])) $rec['video'] = PARTYING_OSS . $rec['video'];

            $rec['money'] = 0;
            $rec['money_lock'] = 0;
            $rec['money_cash'] = 0;
            $rec['money_order'] = 0;
            $rec['gold_coin'] = 0;
            $rec['money_debts'] = 0;
            $rec['bname'] = XsBrokerUser::getBrokerNameByUid($rec['uid']);
            $mdata = XsUserMoney::findFirst($rec['uid']);
            if ($mdata) {
                $rec['money'] = $mdata->money / 100;
                $rec['money_cash_b'] = $mdata->money_cash_b / 100;
                $rec['money_lock'] = $mdata->money_lock / 100;
                $rec['money_cash'] = $mdata->money_cash / 100;
                $rec['money_order'] = $mdata->money_order / 100 + $mdata->money_b / 100 + $mdata->money_order_b / 100;
                $rec['gold_coin'] = $mdata->gold_coin;
                $rec['money_debts'] = isset($mdata->money_debts) ? $mdata->money_debts / 100 : '-';
            } else {
                $rec['money_debts'] = '-';
            }

            $rec['danger_receive'] = 0;
            $mdanger = XsDangerMoney::findFirst($rec['uid']);
            if ($mdanger) {
                $rec['danger_receive'] = $mdanger->money_receive / 100;
            }

            $uarea = XsUserCountry::findFirst($rec['uid']);
            if ($uarea) {
                if ($lang == 'en') {
                    $rec['uarea'] = $uarea->latest_country_code;
                } else {
                    $rec['uarea'] = $uarea->country;
                }
            }
            list($rec['uarea'], $rec['city']) = Helper::transformAreaToArray($rec['uarea'] ?? '', $rec['city'] ?? '');

            $rec['vip'] = $priceLevel[$rec['uid']] ?? 0;
            $rec['pay_room_money'] = $rec['pay_room_money'] / 100;
            $rec['is_service_forbidden'] = ($rec['service_forbidden_dateline'] && $rec['service_forbidden_dateline'] > $now) ? 1 : 0;

            if (isset($userTitleNew[$rec['uid']]) && $userTitleNew[$rec['uid']]['subscribe_time'] > $now) {
                $rec['title_new'] = !empty($rec['level']) ? $rec['level'] : $userTitleNew[$rec['uid']]['level'];
            } else {
                $rec['title_new'] = '';
            }

            $rec['language'] = isset($userLanguage[$rec['uid']]) ? $userLanguage[$rec['uid']]['language'] : $rec['language'];
            $rec['language'] = Helper::getLanguageName($rec['language']);
            $rec['big_area'] = Helper::getUserBigAreaNew($rec['uid']); //新运营大区
        }

        return ['data' => $res, 'total' => $total];
    }

    public static function hasFiftyUserLevelPurview(): bool
    {
        $user = Helper::getSystemUserInfo();
        if ($user['super'] != 1) {
            $purviews = CmsModuleUser::getUserAllAction(Helper::getSystemUid());
            $auth = 'operate/user/user.fifty';
            if (!in_array($auth, $purviews)) {
                return false;
            }
        }
        return true;
    }

    public static function getListByUid(array $uid, string $field)
    {
        $data = self::getListByWhere([
            ['uid', 'IN', $uid],
            ['app_id', '=', APP_ID]
        ], $field);

        if (empty($data)) {
            return array();
        }

        return array_column($data, 'uid', 'uid');
    }

    /**
     * 根据uid批量获取用户信息
     * @param array $uidArr uid
     * @param array $fieldArr 查询的字段
     * @return array
     */
    public static function getUserProfileBatchChunk($uidArr = [], $fieldArr = ['uid', 'name'])
    {
        if (empty($uidArr)) {
            return [];
        }
        if (!in_array('uid', $fieldArr)) {
            $fieldArr[] = 'uid';
        }
        $uidChunk = array_chunk($uidArr,200);
        $dataMap = [];
        foreach ($uidChunk as $uids){
            $data = self::find(array(
                'columns' => implode(',', $fieldArr),
                'conditions' => "uid in ({uid:array}) and app_id = :app_id:",
                'bind' => array(
                    'uid' => $uids,
                    'app_id' => APP_ID,
                ),
            ))->toArray();
            if($data){
                foreach ($data as $v){
                    $dataMap[$v['uid']] = $v;
                }
            }
        }
        return $dataMap;
    }
}
