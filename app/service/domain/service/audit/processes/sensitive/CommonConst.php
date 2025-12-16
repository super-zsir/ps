<?php

namespace Imee\Service\Domain\Service\Audit\Processes\Sensitive;

/**
 * 敏感词配置
 */
class CommonConst
{
    // 危险等级
    public const DIRTY_TEXT_DANGER_NORMAL = 0;
    public const DIRTY_TEXT_DANGER_HIGH = 1;
    public static $danger = [
        self::DIRTY_TEXT_DANGER_NORMAL => '一般',
        self::DIRTY_TEXT_DANGER_HIGH => '高危',
    ];

    // 状态
    public const DIRTY_TEXT_DELETED_NORMAL = 0;
    public const DIRTY_TEXT_DELETED_DELETED = 1;
    public static $dirtyTextDeleted = [
        self::DIRTY_TEXT_DELETED_NORMAL => '正常',
        self::DIRTY_TEXT_DELETED_DELETED => '禁用',
    ];


    // 是否匹配拼音
    public const DIRTY_TEXT_VAGUE_NO = 0;
    public const DIRTY_TEXT_VAGUE_YES = 1;
    public static $vague = [
        self::DIRTY_TEXT_VAGUE_NO => '不匹配',
        self::DIRTY_TEXT_VAGUE_YES => '匹配'
    ];

    /**
     * 是否精准匹配
     */
    public static $displayAccurate = [
        1 => '非精准匹配',
        2 => '精准匹配',
        3 => '包含匹配',
    ];
}
