<?php

namespace Imee\Models\Xs;

class XsGreedyProfit extends BaseModel
{
    /**
     * 批量获取用户明细
     * @param array $roundIds
     * @return array
     */
    public static function getGreedyProfitUserBatch(array $roundIds): array
    {
        $data = self::getListByWhere([
            ['round_id', 'in', $roundIds]
        ], 'id,round_id,op,profit');

        if (!empty($data)) {
            $data = array_column($data, null, 'round_id');
        }
        return $data;
    }
}