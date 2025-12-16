<?php

namespace Imee\Models\Xs;

use Imee\Service\Helper;

class XsEmoticons extends BaseModel
{
    protected static $primaryKey = 'id';

    const NOT_LISTED_STATUS = 0;
    const LISTED_STATUS = 1;
    const DELETE_STATUS = 2;

    public static $statusMap = [
        self::NOT_LISTED_STATUS => '未上架',
        self::LISTED_STATUS     => '已上架',
    ];

    const EMOTICONS_IDENTITY_UNKNOWN = 0;
    const EMOTICONS_IDENTITY_ALL = 1;
    const EMOTICONS_IDENTITY_FAMILY = 2;
    const EMOTICONS_IDENTITY_USER = 3;
    const EMOTICONS_IDENTITY_VIP = 4;
    const EMOTICONS_IDENTITY_SELL = 5;
    const EMOTICONS_IDENTITY_ACTIVE = 6;
    const EMOTICONS_IDENTITY_FAMILY_LEVEL = 7;

    public static $identityMap = [
        self::EMOTICONS_IDENTITY_ALL          => '所有人',
        self::EMOTICONS_IDENTITY_FAMILY       => '特定家族',
        self::EMOTICONS_IDENTITY_USER         => '特定用户',
        self::EMOTICONS_IDENTITY_VIP          => 'VIP用户',
        self::EMOTICONS_IDENTITY_SELL         => '限时购买',
        self::EMOTICONS_IDENTITY_ACTIVE       => '活动奖励',
        self::EMOTICONS_IDENTITY_FAMILY_LEVEL => '特定家族等级',
    ];

    public static $familyLevelMap = [
        5 => 'Lv5',
        6 => 'Lv6',
        7 => 'Lv7',
        8 => 'Lv8',
    ];

    public static function getOptions(): array
    {
        $sql = <<<SQL
SELECT
	e.id AS id,
	g.name AS name,
    e.bigarea_id AS bigarea_id
FROM
	xs_emoticons AS e
	LEFT JOIN xs_emoticons_group g ON e.group_id = g.id 
WHERE
	e.status = 1 
    AND e.identity = 6
SQL;

        $data = Helper::fetch($sql, null, self::SCHEMA_READ);

        $map = [];
        foreach ($data as $item) {
            $map[] = [
                'label'    => $item['id'] . '-' . $item['name'],
                'value'    => (string)$item['id'],
                'big_area' => $item['bigarea_id']
            ];
        }

        return $map;
    }

    public function getPayOption(): array
    {
        $groups = XsEmoticonsGroup::getListByWhere([['pay', '=', XsEmoticonsGroup::PAY_YES]], 'id,name');
        if (!$groups) {
            return [];
        }
        $data = self::getListByWhere([['group_id', 'in', array_column($groups, 'id')], ['status', '=', self::LISTED_STATUS]], 'id,group_id');
        if (!$data) {
            return [];
        }
        $groups = array_column($groups, 'name', 'id');
        $result = [];

        foreach ($data as $item) {
            $groupName = $groups[$item['group_id']];
            $result[$item['id']] = "{$item['id']} -【ID:{$item['group_id']}】{$groupName}";
        }

        return $result;
    }
}