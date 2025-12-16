<?php

namespace Imee\Models\Xs;

/**
 * 用户置顶卡发放记录
 */
class XsUserRoomTopCard extends BaseModel
{
    protected static $primaryKey = 'id';

    const SOURCE_GRANT = 1;
    const SOURCE_GIVE = 2;

    public static $sourceMap = [
        self::SOURCE_GRANT => '发放',
        self::SOURCE_GIVE => '赠送',
    ];

    public static function getJoinCount(array $condition)
    {
        $modelsManager = self::modelsManager();
        $builder = $modelsManager->createBuilder()
            ->columns('count(distinct r.uid,r.room_top_card_id) as cnt')
            ->addfrom(self::class, 'r')
            ->leftJoin(XsUserBigarea::class, 'r.uid = b.uid', 'b')
            ->leftJoin(XsRoomTopCard::class, 'r.room_top_card_id = c.id', 'c');
        list($builder, $_) = self::parseCondition($builder, $condition);
        $total = $builder->getQuery()->execute()->toArray();
        return $total[0]['cnt'] ?? 0;
    }

    public static function getListJoinTable(array $condition, array $columns, string $order = '', int $page = 0, int $pageSize = 0)
    {
        $total = self::getJoinCount($condition);
        if ($total == 0) {
            return ['data' => [], 'total' => 0];
        }
        $modelsManager = self::modelsManager();
        $builder = $modelsManager->createBuilder()
            ->columns($columns)
            ->addfrom(self::class, 'r')
            ->leftJoin(XsUserBigarea::class, 'r.uid = b.uid', 'b')
            ->leftJoin(XsRoomTopCard::class, 'r.room_top_card_id = c.id', 'c')
            ->groupBy('r.uid,r.room_top_card_id');
        list($builder, $_) = self::parseCondition($builder, $condition);
        if (!empty($order)) {
            $builder->orderBy($order);
        }
        if ($page && $pageSize) {
            $startLimit = ($page - 1) * $pageSize;
            $builder->limit($pageSize, $startLimit);
        }

        $data = $builder->getQuery()->execute()->toArray();
        return ['data' => $data, 'total' => $total];
    }
}