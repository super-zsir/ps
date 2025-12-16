<?php

namespace Imee\Models\Xs;

class XsSicBoProfit extends BaseModel
{
    protected static $primaryKey = 'id';

    /**
     * 批量获取用户明细
     * @param array $roundIds
     * @return array
     */
    public static function getSicBoProfitUserBatch(array $roundIds): array
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