<?php

namespace Imee\Models\Xs;

use Imee\Service\Helper;

class XsChatroomBackgroundMall extends BaseModel
{
    const OFF_STATE = 0;
    const ON_STATE = 1;

    /**
     * 获取房间背景只取运营后台的
     *
     * @return array
     */
    public static function  getOptions(): array
    {
        $sql = <<<SQL
SELECT
	a.bg_id AS id,
	a.name AS name 
FROM
	xs_chatroom_background_mall AS a
	LEFT JOIN xs_chatroom_material b ON a.mid = b.mid 
WHERE
	b.source = 0;
SQL;

        $data = Helper::fetch($sql, null, self::SCHEMA_READ);

        $map = [];
        foreach ($data as $item) {
            $map[$item['id']] = $item['id'] . '-' . $item['name'];
        }

        return $map;
    }

    public static function getInfo(int $id): array
    {
        $background = self::findOneByWhere([
            ['bg_id', '=', $id]
        ]);

        if (empty($background)) {
            return [];
        }
        $material = XsChatroomMaterial::findOneByWhere([
            ['mid', '=', $background['mid'] ?? 0],
            ['source', '=', 0]
        ]);

        return $material ? $background : [];
    }
}