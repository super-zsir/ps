<?php

namespace Imee\Models\Xs;

use Imee\Service\Helper;

class XsPropCard extends BaseModel
{
    public static $primaryKey = 'id';

    const DELETED_YES = 1;
    const DELETED_NO = 0;

    public static $deletedMaps = [
        self::DELETED_YES => '下架',
        self::DELETED_NO => '上架中',
    ];

    public static function getOptions()
    {
        $sql = <<<SQL
SELECT
	a.id AS id,
	b.name_json AS name 
FROM
	xs_prop_card AS a
	LEFT JOIN xs_prop_card_config b ON a.prop_card_config_id = b.id 
WHERE
	b.type = 4
AND 
    a.deleted = 0;
SQL;

        $data = Helper::fetch($sql, null, self::SCHEMA_READ);
        $map = [];
        foreach ($data as $item) {
            $name = @json_decode($item['name'], true)['cn'] ?? '';
            $map[$item['id']] = $item['id'] . '-' . $name;
        }

        return $map;
    }

    public static function getPkPropCardOptions()
    {
        $sql = <<<SQL
SELECT
	a.id AS id,
	b.name_json AS name 
FROM
	xs_prop_card AS a
	LEFT JOIN xs_prop_card_config b ON a.prop_card_config_id = b.id 
WHERE
	b.type IN (5,6)
AND 
    a.deleted = 0;
SQL;

        $data = Helper::fetch($sql, null, self::SCHEMA_READ);
        $map = [];
        foreach ($data as $item) {
            $name = @json_decode($item['name'], true)['cn'] ?? '';
            $map[$item['id']] = $item['id'] . '-' . $name;
        }

        return $map;
    }

    /**
     * 获取道具解封时间
     * @param $propCardId
     * @return int
     */
    public static function getPropCardHoursByPropCardId($propCardId): int
    {
        $propCard = self::findOneByWhere([['prop_card_config_id', '=', $propCardId]]);
        $extend = @json_decode($propCard['extend'], true);
        return $extend['hours'] ?? 0;
    }

    public static function getInfo(int $id): array
    {
        return self::findOneByWhere([
            ['id', '=', $id],
            ['deleted', '=', self::DELETED_NO]
        ]);
    }
}