<?php

namespace Imee\Models\Xs;

class XsGreedyRoundEngine extends BaseModel
{
    const END_STATE = 3;

    /**
     * @param array $condition
     * @param string $joinCondition 'from.mid=to.mid'
     * @param string $order
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public static function getListJoinUserLog(array $condition, string $joinCondition, string $order = '', int $page = 0, int $pageSize = 0): array
    {
        $fromTableName = self::getTableName();
        $toTableName = XsGreedyUserLog::getTableName();
        //不能用.*
        $columns = [
            "sum({$toTableName}.prize) as prize",
            "sum({$toTableName}.bet_money) as bet_money",
            $toTableName . '.round_id',
            $fromTableName . '.start_time',
            $fromTableName . '.prize_id',
            $fromTableName . '.prize_pool',
            $fromTableName . '.engine_id',
        ];
        $modelsManager = self::modelsManager();
        $builder = $modelsManager->createBuilder()
            ->columns('count(*) as cnt')
            ->addfrom(self::class, $fromTableName)
            ->leftjoin(XsGreedyUserLog::class, $joinCondition, $toTableName);
        list($builder, $_) = self::parseCondition($builder, $condition);
        $total = $builder->getQuery()->execute()->toArray();
        $total = $total[0]['cnt'] ?? 0;
        if (!$total) {
            return ['data' => [], 'total' => 0];
        }

        $modelsManager = self::modelsManager();
        $builder = $modelsManager->createBuilder()
            ->columns($columns)
            ->addfrom(self::class, $fromTableName)
            ->leftjoin(XsGreedyUserLog::class, $joinCondition, $toTableName);
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