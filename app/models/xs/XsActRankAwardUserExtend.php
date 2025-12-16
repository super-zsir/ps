<?php

namespace Imee\Models\Xs;

class XsActRankAwardUserExtend extends BaseModel
{
    protected static $primaryKey = 'id';

    const EXTEND_TYPE_BR = 0;

    public static $extendType = [
        '0'  => '公会榜',
        '1'  => '语音送礼榜',
        '2'  => '语音收礼榜',
        '11' => '视频送礼榜',
        '12' => '视频收礼榜',
        '21' => '视频语音送礼榜',
        '22' => '视频语音收礼榜',
        '41' => '私聊送礼榜',
        '42' => '私聊收礼榜',
        '51' => '私聊语音送礼榜',
        '52' => '私聊语音收礼榜',
        '61' => '私聊视频送礼榜',
        '62' => '私聊视频收礼榜',
        '71' => '私聊视频语音送礼榜',
        '72' => '私聊视频语音收礼榜',
    ];

    public static function getListByListIdAndExtendId($listId, $extendId, $type, $cycle = 0)
    {
        return self::getListByWhere([
            ['list_id', '=', $listId],
            ['extend_id', '=', $extendId],
            ['extend_type', '=', $type],
            ['cycle', '=', $cycle]
        ], 'id, object_id,score,list_id,extend_id, updated_at, extend_type, cycle', 'score desc,updated_at asc');
    }

    public static function getByListAndExtend(array $listIds, array $extendIds, int $type, array $cycles): array
    {
        $data = self::getListByWhere([
            ['list_id', 'in', $listIds],
            ['extend_id', 'in', $extendIds],
            ['extend_type', '=', $type],
            ['cycle', 'IN', $cycles]
        ], 'id, object_id,score,list_id,extend_id, updated_at, extend_type, cycle', 'score desc,updated_at asc');

        $result = [];

        foreach ($data as $item) {
            $key = $item['list_id'] . '_' . $item['extend_id'] . '_' . $item['cycle'];
            if (!isset($result[$key])) {
                $result[$key] = [];
            }

            $result[$key][] = $item;
        }

        return $result;
    }
}