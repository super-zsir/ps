<?php

namespace Imee\Models\Xs;

class XsEmoticonsMeta extends BaseModel
{
    protected static $primaryKey = 'id';

    const ODDS_EMOTICONS = 1;
    const NO_ODDS_EMOTICONS = 0;

    public static function getList(array $conditions , string $fields): array
    {
        $list = self::getListByWhere($conditions, $fields, 'id desc');
        foreach ($list as &$item) {
            $detail = json_decode($item['detail'], true);
            $item['name'] = $detail[0]['name']['cn'] ?? '';
        }

        return $list;
    }
}