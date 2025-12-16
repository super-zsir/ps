<?php

namespace Imee\Models\Config;

class BbcRankButtonList extends BaseModel
{
    protected static $primaryKey = 'id';
    protected $allowEmptyStringArr = ['button_tag_id'];

    const RANK_TAG_CHARM = 1;                       // 魅力榜
    const RANK_TAG_CONTRIBUTION = 2;                // 贡献榜
    const RANK_TAG_ROOM_FLOW = 3;                   // 房间流水榜
    const RANK_TAG_SCORE = 4;                       // 积分榜
    const RANK_TAG_CUSTOMIZED_GIFT = 5;             // 定制礼物榜;
    const RANK_TAG_GIFT_GROUP = 6;                  // 礼物组榜
    const RANK_TAG_SUB_ACCEPT = 7;                  // 子收礼榜
    const RANK_TAG_SUB_PAY = 8;                     // 子送礼榜
    const RANK_TAG_WEEK_STAR_GIFT = 9;              // 周星礼物榜
    const RANK_TAG_WEEK_STAR_GIFT_SUB_ACCEPT = 10;  // 周星礼物子收礼榜
    const RANK_TAG_WEEK_STAR_GIFT_SUB_SEND = 11;    // 周星礼物子送礼榜
    const RANK_TAG_ONE_PK = 12;
    const RANK_TAG_WEEK_STAR_GIFT_SUB_PAY = 11;     // 周星礼物子送礼榜
    const RANK_TAG_TOP_UP_SCORE = 13;               // 钻石充值榜
    const RANK_TAG_ROOM_SCORE = 14;                 // 房间贡献榜
    const RANK_TAG_ARCHER_SCORE = 15;               // 主播贡献榜

    public static $rankTag = [
        self::RANK_TAG_CHARM                     => '魅力榜',
        self::RANK_TAG_CONTRIBUTION              => '贡献榜',
        self::RANK_TAG_ROOM_FLOW                 => '房间流水榜',
        self::RANK_TAG_SCORE                     => '积分榜',
        self::RANK_TAG_CUSTOMIZED_GIFT           => '礼物榜',
        self::RANK_TAG_GIFT_GROUP                => '礼物组榜',
        self::RANK_TAG_SUB_ACCEPT                => '礼物组子收礼榜',
        self::RANK_TAG_SUB_PAY                   => '礼物组子送礼榜',
        self::RANK_TAG_WEEK_STAR_GIFT            => '周星礼物榜',
        self::RANK_TAG_WEEK_STAR_GIFT_SUB_ACCEPT => '周星礼物子收礼榜',
        self::RANK_TAG_WEEK_STAR_GIFT_SUB_SEND   => '周星礼物子送礼榜',
        self::RANK_TAG_TOP_UP_SCORE              => '充值钻石榜',
        self::RANK_TAG_ROOM_SCORE                => '房间贡献榜'
    ];

    const UPGRADE_TYPE_ONE = 0;//名次
    const UPGRADE_TYPE_TWO = 1;//积分门槛
    const UPGRADE_TYPE_THREE = 2;//名次+积分门槛
    const UPGRADE_TYPE_FOUR = 3;//名次或积分门槛

    // 统计范围
    const ROOM_SUPPORT_VOICE = 0;                           // 语音房
    const ROOM_SUPPORT_VIDEO = 1;                           // 视频房
    const ROOM_SUPPORT_VOICE_AND_VIDEO = 2;                 // 语音视频房
    const ROOM_SUPPORT_NO_MATTER = 3;                       // 与房型无关
    const ROOM_SUPPORT_PRIVATE_CHAT = 4;                    // 私聊
    const ROOM_SUPPORT_PRIVATE_CHAT_AND_VOICE = 5;          // 私聊+语音房
    const ROOM_SUPPORT_PRIVATE_CHAT_AND_VIDEO = 6;          // 私聊+视频房
    const ROOM_SUPPORT_PRIVATE_CHAT_VOICE_AND_VIDEO = 7;    // 私聊+语音房+视频房

    const CP_GENDER_BG = 0;  // 男女
    const CP_GENDER_ALL = 1; // 全部

    public static $roomSupportMap = [
        self::ROOM_SUPPORT_VOICE                        => '语音房',
        self::ROOM_SUPPORT_VIDEO                        => '视频房',
        self::ROOM_SUPPORT_VOICE_AND_VIDEO              => '语音房和视频房',
        self::ROOM_SUPPORT_NO_MATTER                    => '与房型无关',
        self::ROOM_SUPPORT_PRIVATE_CHAT                 => '私聊',
        self::ROOM_SUPPORT_PRIVATE_CHAT_AND_VOICE       => '私聊+语音房',
        self::ROOM_SUPPORT_PRIVATE_CHAT_AND_VIDEO       => '私聊+视频房',
        self::ROOM_SUPPORT_PRIVATE_CHAT_VOICE_AND_VIDEO => '私聊+语音房+视频房',
    ];

    const DIVIDE_TYPE_RECEIVE_GIFT = 0;         // 收礼水平
    const DIVIDE_TYPE_JOIN_BROKER = 1;          // 首次入会时间
    const DIVIDE_TYPE_LAST_JOIN_BROKER = 2;    // 过去多少天入会
    const DIVIDE_TYPE_WEALTH_LEVEL = 3;        // 按财富等级

    const DIVIDE_OBJECT_BROKER = 0;
    const DIVIDE_OBJECT_BROKER_USER = 1;

    const IS_UPGRADE_NO = 0;//不是晋级赛
    const IS_UPGRADE_YES = 1;//晋级赛

    const DIVIDE_TRACK_NO = 0;
    const DIVIDE_TRACK_YES = 1;

    const HAS_PRIZE_POOL_NO = 0; // 不设置奖池
    const HAS_PRIZE_POOL_YES = 1; // 设置奖池

    public static $divideTrackMap = [
        self::DIVIDE_TRACK_NO  => '否',
        self::DIVIDE_TRACK_YES => '是',
    ];

    // 玩法等级映射
    public static $wheelLotteryLevelMap = [
        1 => '低级',
        2 => '中级',
        3 => '高级',
    ];

    public static $isOnlyCorssRoomPkMap = [
        0 => '否',
        1 => '是'
    ];

    const IS_AWARD_NO = 0; // 不发放奖励
    const IS_AWARD_YES = 1; // 发放奖励

    // 是否设置解锁门槛-否
    const IS_SCORE_NO = 1;
    // 是否设置解锁门槛-是
    const IS_SCORE_YES = 2;

    public static $isScoreMap = [
        self::IS_SCORE_NO  => '否',
        self::IS_SCORE_YES => '是',
    ];

    const SCORE_TYPE_RANGE = 1;
    const SCORE_TYPE_ELT = 2;
    const SCORE_TYPE_EGT = 3;

    // 房间类型多选时映射值
    public static $roomSupportMapping = [
        '0,1' => BbcRankButtonList::ROOM_SUPPORT_VOICE_AND_VIDEO,
        '0,4' => BbcRankButtonList::ROOM_SUPPORT_PRIVATE_CHAT_AND_VOICE,
        '1,4' => BbcRankButtonList::ROOM_SUPPORT_PRIVATE_CHAT_AND_VIDEO,
    ];

    public static function getInfoByActIdAndTag($actId, $tag)
    {
        return BbcRankButtonList::findOneByWhere([
            ['act_id', '=', $actId],
            ['rank_tag', '=', $tag]
        ]);
    }

    /**
     * 根据活动id获取榜单map
     * @param int $actId
     * @return array
     */
    public static function getButtonListMapByActId(int $actId): array
    {
        if (empty($actId)) {
            return [];
        }

        $activity = BbcTemplateConfig::findOne($actId);
        if (empty($activity)) {
            return [];
        }

        $conditions = [
            ['act_id', '=', $actId]
        ];

        // 多组礼物榜单只选择主榜
        if ($activity['vision_type'] == BbcTemplateConfig::VISION_TYPE_FOUR) {
            $conditions[] = ['rank_tag', '=', self::RANK_TAG_GIFT_GROUP];
        }

        // 定制礼物只取tag 为left 的榜单（解决脏数据问题）
        if ($activity['vision_type'] == BbcTemplateConfig::VISION_TYPE_CUSTOMIZED) {
            $tag = BbcRankButtonTag::findOneByWhere([
                ['act_id', '=', $actId],
                ['button_tag_type', '=', 'left']
            ], 'id');

            $conditions[] = ['button_tag_id', '=', $tag['id'] ?? 0];
        }


        $buttonList = self::getListByWhere($conditions, 'id, button_content');

        $map = [];

        foreach ($buttonList as $item) {
            $map[$item['id']] = $item['id'] . '-' . $item['button_content'];
        }

        return $map;
    }

    /**
     * 获取最大数据id
     * @return int
     */
    public static function getMaxId(): int
    {
        $data = self::findFirst([
            'columns' => 'MAX(id) as max_id',
        ])->toArray();

        return $data ? $data['max_id'] : 1;
    }

    /**
     * 获取周星子榜单是否发奖
     *
     * @param int $actId
     * @return int
     */
    public static function getWeekStarListIsAward(int $actId): int
    {
        $info = self::findOneByWhere([
            ['act_id', '=', $actId],
            ['is_award', '=', self::IS_AWARD_YES],
            ['rank_tag', '<>', self::RANK_TAG_WEEK_STAR_GIFT]
        ]);

        return $info ? self::IS_AWARD_YES : self::IS_AWARD_NO;
    }

    public static function getInfoByTagAndActAndLevel($tagId, $actId, $level, $id = 0)
    {
        return self::findOneByWhere([
            ['id', '<>', $id],
            ['button_tag_id', '=', $tagId],
            ['act_id', '=', $actId],
            ['level', '=', $level]
        ]);
    }

    public static function getListByRankType(int $actId, int $rankType): array
    {
        return self::getListByWhere([
            ['act_id', '=', $actId],
            ['rank_tag', '=', $rankType]
        ]);
    }

    /**
     * 初始化buttonList数据
     *
     * @return array
     */
    public static function getInitData(): array
    {
        return [
            'act_id'                    => 0,
            'button_tag_id'             => 0,
            'p_level'                   => '',
            'rank_tag'                  => 0,
            'level'                     => 1,
            'is_upgrade'                => 0,
            'start_time'                => 0,
            'end_time'                  => 0,
            'upgrade_num'               => 0,
            'rank_list_num'             => 0,
            'is_award'                  => 0,
            'is_open'                   => 0,
            'has_awarded'               => 0,
            'is_undertake'              => 0,
            'award_time'                => 0,
            'admin_id'                  => 0,
            'dateline'                  => 0,
            'button_content'            => '',
            'button_desc'               => '',
            'room_support'              => 0,
            'upgrade_type'              => 0,
            'upgrade_score'             => 0,
            'upgrade_extend_num'        => 0,
            'cp_gender'                 => 0,
            'official_uid'              => 0,
            'show_honour'               => 0,
            'honour_desc'               => '',
            'divide_track'              => 0,
            'days'                      => 0,
            'score_max'                 => 0,
            'score_min'                 => 0,
            'hide_score'                => 0,
            'divide_type'               => 0,
            'broker_distance_start_day' => 0,
            'has_prize_pool'            => 0,
            'prize_pool_proportion'     => 0.00,
            'divide_object'             => 0,
            'cycle_days'                => 0,
            'cycle_limit'               => 0
        ];
    }

    /**
     * 获取多组礼物主榜信息
     *
     * @param int $actId
     * @param int $tagId
     * @return array
     */
    public static function getMultiGroupGiftList(int $actId, int $tagId): array
    {
        return self::getListByWhere([
            ['act_id', '=', $actId],
            ['button_tag_id', '=', $tagId],
            ['rank_tag', '=', self::RANK_TAG_GIFT_GROUP]
        ], 'id as list_id, button_content, button_desc, is_upgrade, level, upgrade_type, upgrade_num, upgrade_score, start_time, end_time', 'id asc');
    }

    /**
     * 获取周星礼物主榜信息
     *
     * @param int $actId
     * @param int $tagId
     * @return array
     */
    public static function getWeekStarMasterList(int $actId, int $tagId): array
    {
        return self::findOneByWhere([
            ['act_id', '=', $actId],
            ['button_tag_id', '=', $tagId],
            ['rank_tag', '=', self::RANK_TAG_WEEK_STAR_GIFT]
        ]);
    }
}