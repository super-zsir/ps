<?php

namespace Imee\Models\Xs;

class XsUserCertificationSign extends BaseModel
{
    protected static $primaryKey = 'id';

    const WEAR_STATUS_YES = 1;
    const WEAR_STATUS_NO = 0;

    public static function getListJoinMaterials(array $condition, string $joinCondition, string $order = '', int $page = 0, int $pageSize = 0)
    {
        $fromTableName = self::getTableName();
        $toTableName = XsCertificationSign::getTableName();
        //不能用.*
        $columns = [
            $toTableName   . '.name',
            $fromTableName . '.id',
            $fromTableName . '.uid',
            $fromTableName . '.cer_id',
            $fromTableName . '.content',
            $fromTableName . '.expire_dateline',
            $fromTableName . '.create_dateline',
        ];
        $modelsManager = self::modelsManager();
        $builder = $modelsManager->createBuilder()
            ->columns('count(*) as cnt')
            ->addfrom(self::class, $fromTableName)
            ->leftjoin(XsCertificationSign::class, $joinCondition, $toTableName);
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
            ->leftjoin(XsCertificationSign::class, $joinCondition, $toTableName);
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