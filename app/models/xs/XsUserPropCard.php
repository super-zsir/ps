<?php

namespace Imee\Models\Xs;

/**
 * 用户解封卡发放记录
 */
class XsUserPropCard extends BaseModel
{
    protected static $primaryKey = 'id';

    public static function getJoinCount(array $conditions, array $joinConditions, string $countColumn): int
    {
        $modelsManager = self::modelsManager();
        $builder = $modelsManager->createBuilder()
            ->columns($countColumn)
            ->addfrom(self::class, 'u');

        foreach ($joinConditions as $join) {
            $builder->leftjoin($join['class'], $join['condition'], $join['table']);
        }

        list($builder, $_) = self::parseCondition($builder, $conditions);
        $total = $builder->getQuery()->execute()->toArray();
        return $total[0]['cnt'] ?? 0;
    }

    public static function getListJoinTable(array $conditions, array $joinConditions, array $columns, string $countColumn, string $order = '', int $page = 0, int $pageSize = 0, string $groupBy = ''): array
    {
        $total = self::getJoinCount($conditions, $joinConditions, $countColumn);
        if ($total == 0) {
            return ['data' => [], 'total' => 0];
        }
        $modelsManager = self::modelsManager();
        $builder = $modelsManager->createBuilder()
            ->columns($columns)
            ->addfrom(self::class, 'u');

        foreach ($joinConditions as $join) {
            $builder->leftjoin($join['class'], $join['condition'], $join['table']);
        }
        $groupBy && $builder->groupBy($groupBy);

        list($builder, $_) = self::parseCondition($builder, $conditions);
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