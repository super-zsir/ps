<?php
/**
 * 物品审核表
 */

namespace Imee\Models\Xs;

class XsCommodityAdmin extends BaseModel
{
    protected static $primaryKey = 'cid';

    protected $allowEmptyStringArr = [
        'ext_name', 'jump_page', 'image', 'name_zh_tw', 'name_en',
        'name_ar', 'name_ms', 'name_th', 'name_id', 'name_vi', 'name_ko', 'name_jp', 'name_tr', 'name_pt', 'name_es',
        'tag_ids', 'description', 'image_bg', 'sub_type', 'extra',
    ];

    public static $defendList = [
        1 => 'CP',
        2 => '小甜心',
        3 => '小萌新',
    ];

    public static $radioDefendList = [
        1 => '黄金守护',
        2 => '白银守护',
        3 => '青铜守护',
    ];

    //系列名称
    public static $extraNameSeries = [
        '天蝎座'   => '天蝎座',
        '绚丽彩虹' => '绚丽彩虹',
        '腾云宝剑' => '腾云宝剑',
        '甜心猫咪' => '甜心猫咪',
        '射手座'   => '射手座',
        '小草莓'   => '小草莓',
    ];

    /*public static $typeMap = [
        //'exp'          => '经验值',
        'box'          => '礼盒',
        'key'          => '水晶',
        'header'       => '头像框',
        //'title'        => '爵位',
        'gift'         => '礼物',
        //'defend'       => '守护',
        //'radio-defend' => '电台守护',
        'coupon'       => '优惠券',
        'mounts'       => '坐骑',
        'piece'        => '碎片',
        'bubble'       => '聊天气泡',
        'ring'         => '麦上光圈',
        'effect'       => '入场特效',
        'decorate'     => '主页装扮',
        'marry_ring'   => '结婚戒指',
        'union_box'    => '联盟宝箱',
        'union_header' => '联盟头像框'
    ];*/

    public static $typeMap = [
        'exp'                => '经验值',
        'box'                => '礼盒',
        'key'                => '水晶',
        'header'             => '头像框',
        'title'              => '爵位',
        'gift'               => '礼物',
        'defend'             => '守护',
        'radio-defend'       => '电台守护',
        'coupon'             => '优惠券',
        'mounts'             => '坐骑',
        'piece'              => '碎片',
        'bubble'             => '聊天气泡',
        'ring'               => '麦上光圈',
        'effect'             => '入场特效',
        'decorate'           => '主页装扮',
        'marry_ring'         => '戒指',
        'union_box'          => '联盟宝箱',
        'union_header'       => '联盟头像框',
        'experience_voucher' => '体验券',
    ];

    public static $state = [
        0 => '待审核',
        1 => '审核通过',
        2 => '审核不通过',
    ];

    public static $couponType = [
        'none'         => '无',
        'defend'       => '守护',
        'radio-defend' => '电台守护',
        'title'        => '爵位',
        'gift'         => '礼物',
    ];

    //获取方式
    public static $grantWay = [
        'buy'        => '商场购买',
        'activity'   => '活动发放',
        'vip'        => 'vip发放',
        'title'      => '爵位发放',
        'mission'    => '任务发放',
    ];

    //购买限制
    public static $grantLimit = [
        ''      => '不指定',
        'vip'   => '指定vip',
        'title' => '指定爵位',
        'ka'    => 'KA建联且指定vip等级'
    ];

    //商店出售
    public static $salingShop = [
        0 => '非售卖',
        1 => '商城售卖',
        2 => '金币商城',
        3 => '碎片商城',
        4 => '联盟银币商城',
        5 => '联盟特权商城'
    ];

    //财富类型
    public static $moneyType = [
        'money'        => '星球币物品',
        'coin'         => '金币物品',
        'piece'        => '碎片物品',
        'union_gold'   => '联盟金币物品',
        'union_silver' => '银币物品',
    ];

    //头像框、坐骑能否在开箱子中开出来
    public static $canOpenedByBox = [
        0 => '不可以',
        1 => '可以',
    ];

    //是否新支付
    public static $isContinue = [
        0 => '否',
        1 => '是',
    ];

    //是否礼物面板展示
    public static $showOnPanel = [
        0 => '否',
        1 => '是',
    ];

    //是否必须为新支付
    public static $isOnlyNewpay = [
        0 => '否',
        1 => '是',
    ];

    //KA等级
    public static $grantLimitLevel = [
        'S' => 'S',
        'A' => 'A',
        'B' => 'B',
        'C' => 'C',
    ];

    //物品是否能够赠送 1代表可以 0代表不可以 默认可以赠送
    public static $canGive = [
        1 => '可以',
        0 => '不可以'
    ];

    public static $imageBigarea = [
        'image'       => '中文图片',
//        'image_zh_tw' => '台湾图片',
//        'image_en'    => '英文图片',
//        'image_ar'    => '阿语图片',
    ];

    public static $nameBigarea = [
        'name'       => '中文版名称',
        'name_zh_tw' => '台湾版名称',
        'name_en'    => '英文版名称',
        'name_ar'    => '阿语版名称',
        'name_ms'    => '马来语版名称',
        'name_th'    => '泰语版名称',
        'name_id'    => '印尼语版名称',
        'name_vi'    => '越南语版名称',
        'name_ko'    => '韩语版名称',
        'name_tr'    => '土耳其版名称',
        'name_ja'    => '日语版名称',
        'name_hi'    => '印地语版名称',
        'name_bn'    => '孟加拉语版名称',
        'name_ur'    => '乌尔都语版名称',
        'name_tl'    => '他加禄语版名称',
    ];

    public static $descriptionBigarea = [
        'description' => '物品简介',
    ];

    //物品子类型
    public static $subTypeList = [
        'header'   => '头像框',
        'bubble'   => '聊天气泡',
        'effect'   => '入场特效',
        'decorate' => '主页特效',
        'mounts'   => '座驾',
        'ring'     => '麦上光圈',

        '流行'     => '流行',
        '活动'     => '活动',
        'VIP专属'  => 'VIP专属',
        '爵位专属' => '爵位专属',
        '特权'     => '特权',

        'wedding' => '结婚戒指',
        'single'  => '单身戒指',

        'cq_experience_voucher'    => '猜拳体验券',
        'magic_experience_voucher' => '魔法礼物体验券',

        'interactive_animations' => '动作',
        'interactive_effects'    => '特效',
        'interactive_emojis'     => '表情',
    ];

    //演唱会动作类型
    public static $actionList = [
        'Jump'      => 'Jump',
        'SpinRight' => 'SpinRight',
        'SpinLeft'  => 'SpinLeft',
        'Shake'     => 'Shake',
    ];

    const STATE_WAIT = 0;
    const STATE_PASS = 1;
    const STATE_FAIL = 2;

    public static function getListByCid($cids, $appId)
    {
        $cids = array_unique($cids);
        $cids = array_values($cids);
        return self::find([
            'columns'    => 'cid,ocid,only_newpay',
            'conditions' => 'cid IN ({cid:array}) AND app_id=:app_id: AND state=:state:',
            'bind'       => ['cid' => $cids, 'state' => self::STATE_PASS, 'app_id' => $appId]
        ])->toArray();
    }

    /**
     * 物品与属性列表
     * $condition = [];
     * $condition[] = ['from.time', '>=', $endTime]
     * $condition[] = ['from.time', '=', $endTime]
     * @param array $condition
     * @param string $joinCondition 'from.cid=to.cid'
     * @param string $order
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public static function getListJoinProperty(array $condition, string $joinCondition, string $order = '', int $page = 0, int $pageSize = 0): array
    {
        $fromTableName = self::getTableName();
        $toTableName = XsCommodityPropertyAdmin::getTableName();
        //不能用.*
        $columns = [
            $fromTableName . '.cid',
            $fromTableName . '.group_id',
            $fromTableName . '.name',
            $fromTableName . '.type',
            $fromTableName . '.sub_type',
            $fromTableName . '.description',
            $fromTableName . '.price',
            $fromTableName . '.image',
            $fromTableName . '.state',
            $fromTableName . '.ocid',
            $fromTableName . '.admin',
            $fromTableName . '.mark',
            $fromTableName . '.money_type',
            $toTableName . '.duction_rate',
            $toTableName . '.duction_limit_min',
            $toTableName . '.duction_limit_max',
            $toTableName . '.grant_way',
            $toTableName . '.grant_limit',
            $toTableName . '.grant_limit_range',
            $toTableName . '.weight',
        ];
        $modelsManager = self::modelsManager();
        $builder = $modelsManager->createBuilder()
            ->columns('count(*) as cnt')
            ->addfrom(self::class, $fromTableName)
            ->leftjoin(XsCommodityPropertyAdmin::class, $joinCondition, $toTableName);
        list($builder, $_) = self::parseCondition($builder, $condition);
        $total = $builder->getQuery()->execute()->toArray();
        $total = $total[0]['cnt'] ?? 0;
        if (!$total) {
            return ['data' => [], 'total' => 0];
        }

        $modelsManager = self::modelsManager();
        $builder = $modelsManager->createBuilder()
            ->columns($columns)
            ->addfrom(self::class, $fromTableName)
            ->leftjoin(XsCommodityPropertyAdmin::class, $joinCondition, $toTableName);
        list($builder, $_) = self::parseCondition($builder, $condition);
        if (!empty($order)) {
            $builder->orderBy($order);
        }
        if ($page && $pageSize) {
            $startLimit = ($page - 1) * $pageSize;
            $builder->limit($pageSize, $startLimit);
        }

        $data = $builder->getQuery()->execute()->toArray();
        return ['data' => $data, 'total' => $total];
    }

    // 获取审核成功物品
    public static function getCommodityList(): array
    {
        $conditions = [
            ['ocid', '<>', 0],
            ['state', '=', 1],
            ['app_id', '=', APP_ID],
        ];


        $commoditys = self::getListByWhere($conditions, 'cid, ocid, name', 'cid desc');
        if (empty($commoditys)) {
            return [];
        }

        $data = [];

        foreach ($commoditys as $commodity) {
            $data[$commodity['ocid']] = $commodity['ocid'] . '_' . $commodity['name'];
        }

        return $data;
    }

    public static function getInfo(int $cid): array
    {
        return self::findOneByWhere([
            ['ocid', '=', $cid],
            ['state', '=', 1],
            ['app_id', '=', APP_ID]
        ]);
    }

    public static function getCommodityListByTypes(array $types = []): array
    {
        $conditions = [
            ['ocid', '<>', 0],
            ['state', '=', 1],
            ['app_id', '=', APP_ID],
            ['type', 'IN', $types]
        ];

        $commoditys = self::getListByWhere($conditions, 'cid, ocid, name, type', 'cid desc');

        return $commoditys ? array_column($commoditys, null, 'ocid') : [];
    }

    //系列名称
    public static function getNameSeries(): array
    {
        return [
            ''        => '所有系列',
            '天蝎座'   => '天蝎座',
            '绚丽彩虹' => '绚丽彩虹',
            '腾云宝剑' => '腾云宝剑',
            '甜心猫咪' => '甜心猫咪',
            '射手座'   => '射手座',
            '小草莓'   => '小草莓',
        ];
    }

    /**
     * @param $type string 物品类型
     * @return array
     */
    public static function getSubType($type): array
    {
        switch ($type) {
            case 'piece':
                $keys = ['header', 'bubble', 'effect', 'decorate', 'mounts', 'ring'];
                break;
            case 'marry_ring':
                $keys = ['wedding', 'single'];
                break;
            case 'gift':
                $keys = ['流行', '活动', 'VIP专属', '爵位专属', '特权', 'interactive_animations', 'interactive_effects', 'interactive_emojis'];
                break;
            case 'experience_voucher':
                $keys = ['cq_experience_voucher', 'magic_experience_voucher'];
                break;
            default:
                $keys = ['流行', '活动', 'VIP专属', '爵位专属', '特权'];
        }

        $list = [];
        foreach (self::$subTypeList as $key => $val) {
            if (in_array($key, $keys, true)) {
                $list[$key] = $val;
            }
        }

        return $list;
    }

    public static function getDisabledMap(string $type): array
    {
        $disabledFalse = $disabledTrue = $readOnly = $readOnlyFalse = $defaultValue = $show = $required = [];

        $defaultValue['image'] = '';
        $defaultValue['panel_image'] = '';

        if ($type == 'union_box') {
            $disabledFalse[] = 'poolid';
        } else {
            $disabledTrue[] = 'poolid';
        }

        if ($type != 'coupon') {
            $defaultValue['coupon_type'] = 'none';
        } else {
            $defaultValue['coupon_type'] = '';
        }

        if (in_array($type, ['header', 'mounts', 'effect', 'decorate', 'bubble', 'ring'])) {
            $disabledFalse[] = 'show_on_panel';
        } else {
            $disabledTrue[] = 'show_on_panel';
            $defaultValue['show_on_panel'] = 0;
        }
        if ($type == 'experience_voucher') {
            $disabledTrue[] = 'can_give';
            $disabledTrue[] = 'price';
            $disabledTrue[] = 'money_type';
            $disabledTrue[] = 'can_opened_by_box';
            $defaultValue['price'] = 0;
        } else {
            $disabledFalse += [
                'price', 'money_type', 'can_give', 'saling_on_shop', 'can_opened_by_box',
            ];
        }

        if (in_array($type, ['prop', 'special', 'horn'])) {
            $show[] = 'prop_type';
        }

        $defaultValue['can_opened_by_box'] = '0';
        if (!in_array($type, ['header', 'mounts', 'gift', 'bubble_tail'])) {
            $readOnly[] = ['can_opened_by_box'];
        }

        $defaultValue['sub_type'] = '';

        return compact('disabledFalse', 'readOnly', 'defaultValue', 'show', 'required', 'readOnlyFalse', 'disabledTrue');
    }
}
