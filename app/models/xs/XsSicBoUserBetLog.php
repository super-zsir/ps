<?php

namespace Imee\Models\Xs;

class XsSicBoUserBetLog extends BaseModel
{
    public static function getBetListByWhere(array $conditions): array
    {
        $list = self::getListByWhere($conditions);

        if (empty($list)) {
            return [];
        }
        $initPrice = ['1' => 0, '2' => 0, '3' => 0];
        foreach ($list as $item) {
            $initPrice[$item['bet_id']] += $item['chip_id'] * $item['count'];
        }

        return $initPrice;
    }
}