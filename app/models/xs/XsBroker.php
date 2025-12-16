<?php

namespace Imee\Models\Xs;

use Imee\Service\Helper;

class XsBroker extends BaseModel
{
    protected static $primaryKey = 'id';

    /**
     * 根据bid批量获取公会信息
     * @param array $bidArr bid
     * @param array $fieldArr 查询的字段
     * @return array
     */
    public static function getBrokerBatch($bidArr = [], $fieldArr = ['bid', 'bname'], $column = null)
    {
        if (empty($bidArr)) {
            return [];
        }
        if (!in_array('bid', $fieldArr)) {
            $fieldArr[] = 'bid';
        }

        $data = self::getListByWhere([
            ['bid', 'IN', $bidArr]
        ], implode(',', $fieldArr));

        if (empty($data)) {
            return array();
        }

        return array_column($data, $column, 'bid');
    }

    public static function getByBid($bid, $columns = '*')
    {
        return self::findFirst(array(
            "bid=:bid:",
            "bind" => array("bid" => $bid),
            'columns' => $columns,
        ));
    }

    /**
     * 根据uid获取公会长
     * @param array $uids
     * @return array
     */
    public static function getListByCreater(array $uids): array
    {
        $list = self::getListByWhere([
            ['creater', 'in', $uids],
        ], 'creater');

        return $list ? Helper::arrayFilter($list, 'creater') : [];
    }

    /**
     * 判断是否为公会长
     * @param array $uids
     * @return array
     */
    public static function checkUidBroker(array $uids): array
    {
        $absent = [];
        foreach (array_chunk($uids, 200) as $item) {
            $list = self::getListByCreater($item);
            if (empty($list)) {
                $absent = array_merge($absent, $item);
                continue;
            }
            $diff = array_diff($item, $list);
            $diff && $absent = array_merge($absent, $diff);
        }
        return $absent;
    }

    public static function checkBid(array $bids)
    {
        $absent = [];
        foreach (array_chunk($bids, 200) as $item) {
            $list = self::getListByWhere([
                ['bid', 'in', $item]
            ], 'bid');
            if (empty($list)) {
                $absent = array_merge($absent, $item);
                continue;
            }
            $bids = array_column($list,'bid');
            $diff = array_diff($item, $bids);
            if ($diff) {
                $absent = array_merge($absent, $diff);
            }
        }
        return $absent;
    }

    /**
     * 根据bid批量获取公会信息
     * @param array $bidArr bid
     * @param array $fieldArr 查询的字段
     * @return array
     */
    public static function getBrokerBatchChounk($bidArr = [], $fieldArr = ['bid', 'bname'])
    {
        if (empty($bidArr)) {
            return [];
        }
        if (!in_array('bid', $fieldArr)) {
            $fieldArr[] = 'bid';
        }
        $bidChunk = array_chunk($bidArr,200);
        $dataMap = [];
        foreach ($bidChunk as $bids){
            $data = self::find(array(
                'columns' => implode(',', $fieldArr),
                'conditions' => "bid in ({bid:array})",
                'bind' => array(
                    'bid' => $bids,
                ),
            ))->toArray();
            if($data){
                foreach ($data as $v){
                    $dataMap[$v['bid']] = $v;
                }
            }
        }
        return $dataMap;
    }
}
