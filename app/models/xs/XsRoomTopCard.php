<?php

namespace Imee\Models\Xs;

/**
 * 房间置顶卡
 */
class XsRoomTopCard extends BaseModel
{
    protected static $primaryKey = 'id';

    const DELETE_NO = 0;  // 未删除
    const DELETE_YES = 1; // 已删除

    public static function getList(array $conditions, string $fields): array
    {
        $list = self::getListByWhere($conditions, $fields, 'id desc');
        foreach ($list as &$item) {
            $nameJson = json_decode($item['name_json'], true);
            $item['name'] = $nameJson['cn'] ?? '';
        }

        return $list;
    }

    public static function getOptions(): array
    {
        $data = self::getList([
            ['is_delete', '=', self::DELETE_NO]
        ], 'id, name_json');

        $map = [];
        foreach ($data as $item) {
            $map[$item['id']] = $item['id'] . '-' . $item['name'];
        }
        return $map;
    }

    public static function getInfo(int $id): array
    {
        return self::findOneByWhere([
            ['id', '=', $id],
            ['is_delete', '=', self::DELETE_NO]
        ]);
    }
}