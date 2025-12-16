<?php

namespace Imee\Models\Xs;

class XsGreedyUserLog extends BaseModel
{
    public static function getUidByRoundIds($roundIds, $bigArea)
    {
        $uids = [];

        $list = self::getListByWhere([
            ['round_id', 'in', $roundIds],
            ['bigarea_id', '=', $bigArea]
        ]);

        if ($list) {
            $uids = array_column($list, 'uid');
        }

        return $uids;
    }

    public static function getListJoinRoundEngine(array $condition, string $joinCondition, string $order = '', int $page = 0, int $pageSize = 0)
    {

        $fromTableName = self::getTableName();
        $toTableName = XsGreedyRoundEngine::getTableName();
        //不能用.*
        $columns = [
            $toTableName . '.engine_id',
            $fromTableName . '.round_id',
            $fromTableName . '.uid',
            $fromTableName . '.bigarea_id',
            $fromTableName . '.bet_money',
            $fromTableName . '.prize_id',
            $fromTableName . '.prize',
            $fromTableName . '.dateline',
            $fromTableName . '.extra',
        ];

        $modelsManager = self::modelsManager();
        $builder = $modelsManager->createBuilder()
            ->columns('count(*) as cnt')
            ->addfrom(self::class, $fromTableName)
            ->leftjoin(XsGreedyRoundEngine::class, $joinCondition, $toTableName);
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
            ->leftjoin(XsGreedyRoundEngine::class, $joinCondition, $toTableName);
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