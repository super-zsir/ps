<?php

namespace Imee\Models\Xs;

class XsCustomizePrettyStyle extends BaseModel
{
    public const SCHEMA_READ = 'xsserverslave';
    
    //  0:、英文&数字； 1:英文&数字，与阿语两种  2:英文&数字+土语
    const STYLE_TYPE_ENGLISH_NUMBER = 0;
    const STYLE_TYPE_ENGLISH_NUMBER_ARABIC = 1;
    const STYLE_TYPE_ENGLISH_NUMBER_TR = 2;

    const DISABLED_YES = 1;
    const DISABLED_NO = 0;

    public static $styleTypeMap = [
        self::STYLE_TYPE_ENGLISH_NUMBER        => '英文&数字',
        self::STYLE_TYPE_ENGLISH_NUMBER_ARABIC => '英文&数字, 仅阿语',
        self::STYLE_TYPE_ENGLISH_NUMBER_TR     => '英文&数字, 仅土语',
    ];

    public static $disabledMap = [
        '0' => '启用',
        '1' => '禁用',
    ];

    public static function getOptions(): array
    {
        $data = self::getListByWhere([], 'id, name', 'id desc');

        $map = [];
        foreach ($data as $item) {
            $map[$item['id']] = $item['id'] . '-' . $item['name'];
        }
        return $map;
    }

    public static function getInfo(int $id): array
    {
        return self::findOne($id);
    }
}
