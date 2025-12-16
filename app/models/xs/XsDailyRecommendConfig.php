<?php

namespace Imee\Models\Xs;

class XsDailyRecommendConfig extends BaseModel
{
    public const SCHEMA_READ = 'xsserverslave';

    public static $primaryKey = 'id';

    const RECOMMEND_TYPE_UID = 1;
    const RECOMMEND_TYPE_PAGES = 2;

    public static $recommendTypeMaps = [
        self::RECOMMEND_TYPE_UID   => 'uid',
        self::RECOMMEND_TYPE_PAGES => 'pages'
    ];

    const RECOMMEND_RULE_WHITELIST = 1;
    const RECOMMEND_RULE_VIP = 2;

    public static $recommendRuleMaps = [
        self::RECOMMEND_RULE_WHITELIST => '白名单类型值68的用户',
        self::RECOMMEND_RULE_VIP       => '抓取>=VIP6的用户'
    ];

    const STATUS_NO = 1;
    const STATUS_IN = 2;
    const STATUS_END = 3;

    public static $statusMaps = [
        self::STATUS_NO  => '未生效',
        self::STATUS_IN  => '生效中',
        self::STATUS_END => '已失效'
    ];
}