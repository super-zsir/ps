<?php

namespace Imee\Models\Xs;

use Imee\Comp\Common\Orm\Traits\ChangeTrait;

class XsMedalResource extends BaseModel
{
    use ChangeTrait;

    public static $primaryKey = 'id';

    const PREFIX = 'description_';

    const ACHIEVEMENT_MEDAL = 1;
    const HONOR_MEDAL = 2;

    const GIFT_MEDAL = 3;

    public static $typeMap = [
        self::ACHIEVEMENT_MEDAL => '成就勋章',
        self::HONOR_MEDAL       => '荣誉勋章',
        self::GIFT_MEDAL        => '礼物勋章',
    ];


    const ONLINE_STATUS_YES = 1; // 上架
    const ONLINE_STATUS_NO = 0;  // 下架

    public static function getMedalList(int $type): array
    {
        $res = self::getListByWhere([
            ['type', '=' , $type],
        ], 'id, description_zh_tw', 'id desc');
        if ($res) {
            self::formatDescription($res);
        }
        return $res;
    }

    private static function formatDescription(&$data)
    {
        foreach ($data as &$v) {
            $desc = json_decode($v['description_zh_tw'], true);
            $v['name'] =  $desc['name'] ?? '';
            $v['description'] = $desc['description'] ?? '';
        }
    }

    /**
     * 根据id批量获取勋章信息
     * @param array $ids
     * @param string $field
     * @param null $columns
     * @return array
     */
    public static function getMedalBatch(array $ids, string $field = '*', $columns = null)
    {
        $res = self::getListByWhere([
            ['id', 'in', $ids],
        ], $field);
        if ($res) {
            self::formatDescription($res);
            $res = array_column($res, $columns, 'id');
        }
        return $res;
    }

    public static function getList(array $conditions, $fields = '*', $page = 1, $pageSize = 15)
    {
        $list = self::find([
            'conditions' => implode(' AND ', $conditions['conditions']),
            'bind'       => $conditions['bind'],
            'columns'    => $fields,
            'order'      => 'id desc',
            'limit'      => $pageSize,
            'offset'     => ($page - 1) * $pageSize
        ]);

        if (empty($list)) {
            return ['data' => [], 'total' => 0];
        }

        $total = self::count([
            'conditions' => implode(' AND ', $conditions['conditions']),
            'bind'       => $conditions['bind']
        ]);

        return ['data' => $list->toArray(), 'total' => $total];
    }

    public static function getInfo(int $id, int $type = self::HONOR_MEDAL): array
    {
        return self::findOneByWhere([
            ['id', '=', $id],
            ['type', '=', $type]
        ]);
    }
}