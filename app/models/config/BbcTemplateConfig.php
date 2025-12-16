<?php

namespace Imee\Models\Config;

class BbcTemplateConfig extends BaseModel
{
    protected static $primaryKey = 'id';

    const TYPE_ONE_PK = 'onepk';
    const TYPE_TASK = 'task';
    const TYPE_GIFT_TASK = 'gift_task';
    const TYPE_WHEEL_LOTTERY = 'wheel_lottery';
    const TYPE_RANK = 'rank';
    const TYPE_GIFT_RANK = 'gift_rank';
    const TYPE_MULTI_TASK = 'multi_task';
    const TYPE_EXCHANGE = 'exchange';

    public static $typeMap = [
        self::TYPE_RANK          => '用户维度的榜单',
        self::TYPE_GIFT_RANK     => '礼物维度的榜单',
        self::TYPE_TASK          => '任务玩法',
        self::TYPE_ONE_PK        => '1v1玩法',
        self::TYPE_WHEEL_LOTTERY => '幸运玩法',
        self::TYPE_EXCHANGE      => '积分兑换',
    ];

    public static $typeAndRelateTypeMap = [
        self::TYPE_TASK          => self::ACT_TEMPLATE_TYPE_TASK,
        self::TYPE_MULTI_TASK    => self::ACT_TEMPLATE_TYPE_MUTLI_TASK,
        self::TYPE_WHEEL_LOTTERY => self::ACT_TEMPLATE_TYPE_WHEEL,
    ];

    const VISION_TYPE_ONE = 0;
    const VISION_TYPE_FAMILY = 1;
    const VISION_TYPE_CUSTOMIZED = 2;
    const VISION_TYPE_THREE = 3;
    const VISION_TYPE_FOUR = 4;
    const VISION_TYPE_CP = 5;
    const VISION_TYPE_PROGRESS = 6; // 进度条类型
    const VISION_TYPE_MAP_FORWARD = 7; // 地图前进
    const VISION_TYPE_WHEEL_LOTTERY = 8; // 转盘
    const VISION_TYPE_EGG_TWISTING_MACHINE = 9; // 扭蛋机
    const VISION_TYPE_EXCHANGE = 10; // 积分兑换

    public static $visionType = [
        self::VISION_TYPE_ONE, self::VISION_TYPE_FAMILY, self::VISION_TYPE_CUSTOMIZED,
        self::VISION_TYPE_THREE, self::VISION_TYPE_FOUR, self::VISION_TYPE_CP
    ];

    public static $visionTypeMap = [
        self::VISION_TYPE_ONE         => '基础视觉1',
        self::VISION_TYPE_FAMILY      => '基础视觉2',
        self::VISION_TYPE_CUSTOMIZED  => '定制礼物榜单',
        self::VISION_TYPE_THREE       => '周星礼物活动视觉',
        self::VISION_TYPE_FOUR        => '多组礼物榜单活动视觉',
        self::VISION_TYPE_CP          => 'CP视觉',
        self::VISION_TYPE_PROGRESS    => '进度条类型',
        self::VISION_TYPE_MAP_FORWARD => '地图前进',
    ];

    const STATUS_NOT_RELEASE = 0;           // 未发布
    const STATUS_WAIT_START = 1;            // 待开始
    const STATUS_RELEASE_AUDIT_HAVE = 2;    // 已发布（审核中）
    const STATUS_DISMISS = 3;               // 已打回（需修改）
    const STATUS_RELEASE = 4;               // 已发布
    const STATUS_HAVE = 5;                  // 进行中
    const STATUS_END = 6;                   // 已结束
    const STATUS_REPLENISH_STOCK = 7;       // 补库存（审核中）
    const STATUS_PUBLISH_HAVE = 8;          // 发布中
    const STATUS_PUBLISH_ERROR = 9;         // 发布失败（请重试）

    const ONE_PK_OBJECT_ROOM = 0;
    const ONE_PK_OBJECT_ANCHOR = 1;
    const ONE_PK_OBJECT_BROKER = 2;
    const ONE_PK_OBJECT_FAMILY = 3;

    public static $onePkObject = [
        self::ONE_PK_OBJECT_ROOM   => '房间',
        self::ONE_PK_OBJECT_ANCHOR => '个人',
        self::ONE_PK_OBJECT_BROKER => '公会',
        self::ONE_PK_OBJECT_FAMILY => '家族',
    ];

    public static $statusMap = [
        self::STATUS_NOT_RELEASE   => '未发布',
        self::STATUS_WAIT_START    => '待开始',
        self::STATUS_HAVE          => '进行中',
        self::STATUS_END           => '已结束',
        self::STATUS_PUBLISH_HAVE  => '发布中',
        self::STATUS_PUBLISH_ERROR => '发布失败（请重试）',
    ];

    public static $auditStatusMap = [
        self::STATUS_RELEASE_AUDIT_HAVE => '审核中',
        self::STATUS_DISMISS            => '已打回（需修改）',
        self::STATUS_REPLENISH_STOCK    => '补库存（审核中）',
        self::STATUS_RELEASE            => '审核已通过',
    ];

    const HAS_RELATE_YES = 1;
    const HAS_RELATE_NO = 0;

    public static $hasRelateMap = [
        self::HAS_RELATE_YES => '是',
        self::HAS_RELATE_NO  => '否',
    ];

    public static $pageUrlTypeMap = [
        self::VISION_TYPE_EXCHANGE    => 'exchange',
        self::VISION_TYPE_PROGRESS    => 'task',
        self::VISION_TYPE_MAP_FORWARD => 'map',
    ];

    const CYCLE_TYPE_ONE = 1;
    const CYCLE_TYPE_LOOP = 2;

    const RELATE_TYPE_TASK = 1;
    const RELATE_TYPE_WHEEL_LOTTERY = 2;
    public static $relateTypeMap = [
        self::RELATE_TYPE_TASK          => self::TYPE_TASK,
        self::RELATE_TYPE_WHEEL_LOTTERY => self::TYPE_WHEEL_LOTTERY,
    ];

    const ACT_TEMPLATE_TYPE_RANK = 1; // 榜单模板
    const ACT_TEMPLATE_TYPE_TASK = 2; // 单线任务
    const ACT_TEMPLATE_TYPE_MUTLI_TASK = 3; // 多线独立任务模板
    const ACT_TEMPLATE_TYPE_WHEEL = 4; // 幸运玩法模板
    const ACT_TEMPLATE_TYPE_TOP_UP = 5; // 累充模板
    const ACT_TEMPLATE_TYPE_FIRST_RECHARGE = 6; // 首充模板
    const ACT_TEMPLATE_TYPE_HONOUR_WALL = 7; // 荣誉墙模板
    const ACT_TEMPLATE_TYPE_EXCHANGE = 8; // 积分兑换模板

    public static $actTemplateTypeMap = [
        self::ACT_TEMPLATE_TYPE_TASK       => '单线任务模板',
        self::ACT_TEMPLATE_TYPE_MUTLI_TASK => '多线独立任务模板',
    ];

    public static $actTemplateTypeAndTypeMap = [
        self::ACT_TEMPLATE_TYPE_TASK       => self::TYPE_TASK,
        self::ACT_TEMPLATE_TYPE_MUTLI_TASK => self::TYPE_MULTI_TASK,
    ];

    // 幸运玩法旧版最大id
    const LUCK_GAME_MAX_VERSION_ID = ENV == 'dev' ? 2538 : 4885;
    // 新老版本最大id区分
    const VERSION_ID = ENV == 'dev' ? 2320 : 4553;
    const VERSION_ID_TWO = ENV == 'dev' ? 2584 : 4843;

    public static function getList(array $conditions, $fields = '*', $page = 1, $pageSize = 15)
    {
        $list = BbcTemplateConfig::find([
            'conditions' => implode(' AND ', $conditions['conditions']),
            'bind'       => $conditions['bind'],
            'columns'    => $fields,
            'order'      => 'id desc',
            'limit'      => $pageSize,
            'offset'     => ($page - 1) * $pageSize
        ]);

        if (empty($list)) {
            return ['data' => [], 'total' => 0];
        }

        $total = BbcTemplateConfig::count([
            'conditions' => implode(' AND ', $conditions['conditions']),
            'bind'       => $conditions['bind']
        ]);

        return ['data' => $list->toArray(), 'total' => $total];
    }

    /**
     * 获取活动枚举
     * @return array
     */
    public static function getActivityMap(): array
    {
        $list = self::getListByWhere([
            ['type', 'IN', [self::TYPE_RANK, self::TYPE_GIFT_RANK]],
            ['vision_type', '<>', self::VISION_TYPE_THREE]
        ], 'id, title', 'id desc');

        $map = [];
        foreach ($list as $item) {
            $map[$item['id']] = $item['id'] . '-' . $item['title'];
        }

        return $map;
    }

    /**
     * 是否幸运玩法新版本
     *
     * @param int $id
     * @return bool
     */
    public static function isWheelLotteryNewVersion(int $id): bool
    {
        return $id > self::LUCK_GAME_MAX_VERSION_ID;
    }
}