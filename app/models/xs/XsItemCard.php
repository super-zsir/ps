<?php

namespace Imee\Models\Xs;

use Imee\Service\Helper;

class XsItemCard extends BaseModel
{
    public const SCHEMA_READ = 'xsserverslave';

    const TYPE_MINI = 1; //迷你资料卡装扮
    const TYPE_HOMEPAGE = 2; //个人主页装扮卡片

    /**
     * 字段映射
     * @var array
     */
    public static $jsonFieldMap = [
        XsItemCard::TYPE_MINI => ['minicard_style', 'minicard_background'],
        XsItemCard::TYPE_HOMEPAGE => ['homepage_dress_style', 'homepage_dress_background'],
    ];

    public static $languageMap = [
        'zh_cn' => '简体中文(zh_cn)',
        'zh_tw' => '繁体中文(zh_tw)',
        'en'    => '英语(en)',
        'ar'    => '阿语(ar)',
        'th'    => '泰语(th)',
        'ko'    => '韩语(ko)',
        'ur'    => '巴基斯坦语(ur)',
        'id'    => '印尼语(id)',
        'vi'    => '越南语(vi)',
        'tr'    => '土耳其语(tr)',
        'ms'    => '马来语(ms)',
        'ja'    => '日语(ja)',
        'hi'    => '印度语(hi)',
        'bn'    => '孟加拉语(bn)',
        'tl'    => '菲律宾语(tl)',
    ];

    public static function getByIds(array $ids): array
    {
        if (!$ids) {
            return [];
        }
        $data = self::findByIds($ids, 'id,name_json,icon');
        foreach ($data as &$item) {
            $name = @json_decode($item['name_json'], true);
            $item['name'] = $name['zh_cn'] ?? '';
            $item['icon'] = Helper::getHeadUrl($item['icon']);

            unset($item['name_json']);
        }

        return array_column($data, null, 'id');
    }

    public static function getMap(int $type): array
    {
        $data = self::getListByWhere([
            ['type', '=', $type]
        ], 'id,name_json');
        foreach ($data as &$item) {
            $name = @json_decode($item['name_json'], true);
            $item['name'] = $item['id'] . ' - ' . $name['zh_cn'] ?? '';

            unset($item['name_json']);
        }

        return array_column($data, 'name', 'id');
    }

    public static function getInfo(int $cid)
    {
        return self::findOne($cid);
    }
}