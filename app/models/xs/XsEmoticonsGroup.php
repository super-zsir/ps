<?php

namespace Imee\Models\Xs;

class XsEmoticonsGroup extends BaseModel
{
    protected static $primaryKey = 'id';

    const PAY_NO = 0;
    const PAY_YES = 1;
    const PAY_ACTIVE = 2;

    public static $payMap = [
        self::PAY_NO     => '不可购买',
        self::PAY_YES    => '可购买',
        self::PAY_ACTIVE => '活动奖励',
    ];

    public static $text = [
        'zh_cn' => ['name' => '中文', 'required' => true],
        'en'    => ['name' => '英文', 'required' => true],
        'ar'    => ['name' => '阿语', 'required' => false],
        'tr'    => ['name' => '土耳其', 'required' => false],
        'ko'    => ['name' => '韩语', 'required' => false],
        'th'    => ['name' => '泰语', 'required' => false],
        'ur'    => ['name' => '巴基斯坦', 'required' => false],
        'id'    => ['name' => '印尼', 'required' => false],
        'vi'    => ['name' => '越南', 'required' => false],
        'ms'    => ['name' => '马来', 'required' => false],
        'hi'    => ['name' => '印度', 'required' => false],
        'bn'    => ['name' => '孟加拉', 'required' => false],
        'tl'    => ['name' => '菲律宾', 'required' => false],
    ];
}