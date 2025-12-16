<?php

namespace Imee\Models\Xs;

/**
 * 用户解封卡卡发放记录
 */
class XsSendPropCardLog extends BaseModel
{
    protected static $primaryKey = 'id';

    const SOURCE_BUY = 1;
    const SOURCE_ADMIN_SEND = 2;
    const SOURCE_ACTIVITY_SEND = 3;
    const SOURCE_GIVE = 4;

    public static $sourceMap = [
        self::SOURCE_BUY           => '购买',
        self::SOURCE_ADMIN_SEND    => '后台发放',
        self::SOURCE_ACTIVITY_SEND => '活动发放',
        self::SOURCE_GIVE          => '赠送',
    ];

    /**
     * @param array $condition
     * @param string $order
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public static function getListJoinPropCard(array $condition, string $order = '', int $page = 0, int $pageSize = 0): array
    {
        //不能用.*
        $columns = [
            'l.id',
            'l.uid',
            'l.sender',
            'l.dateline',
            'c.extend',
            'b.bigarea_id',
        ];
        $modelsManager = self::modelsManager();
        $builder = $modelsManager->createBuilder()
            ->columns('count(*) as cnt')
            ->addfrom(self::class, 'l')
            ->leftjoin(XsPropCard::class, 'l.prop_card_id = c.id', 'c')
            ->leftjoin(XsUserBigarea::class, 'l.sender = b.uid', 'b');
        list($builder, $_) = self::parseCondition($builder, $condition);
        $total = $builder->getQuery()->execute()->toArray();
        $total = $total[0]['cnt'] ?? 0;
        if (!$total) {
            return ['data' => [], 'total' => 0];
        }

        $modelsManager = self::modelsManager();
        $builder = $modelsManager->createBuilder()
            ->columns($columns)
            ->addfrom(self::class, 'l')
            ->leftjoin(XsPropCard::class, 'l.prop_card_id = c.id', 'c')
            ->leftjoin(XsUserBigarea::class, 'l.sender = b.uid', 'b');
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