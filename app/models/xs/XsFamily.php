<?php


namespace Imee\Models\Xs;


class XsFamily extends BaseModel
{
    protected static $primaryKey = 'fid';

    public static function checkFidAndBigArea(array $fids, int $bigAreaId)
    {
        $absent = [];
        foreach (array_chunk($fids, 200) as $item) {
            $list = self::getListByWhere([
                ['fid', 'in', $item],
                ['big_area_id', '=', $bigAreaId]
            ], 'fid');
            if (empty($list)) {
                $absent = array_merge($absent, $item);
                continue;
            }
            $fids = array_column($list, 'fid');
            $diff = array_diff($item, $fids);
            if ($diff) {
                $absent = array_merge($absent, $diff);
            }
        }
        return $absent;
    }

    public static function checkFid(array $fids)
    {
        $absent = [];
        foreach (array_chunk($fids, 200) as $item) {
            $list = self::getListByWhere([
                ['fid', 'in', $item]
            ], 'fid');
            if (empty($list)) {
                $absent = array_merge($absent, $item);
                continue;
            }
            $fids = array_column($list, 'fid');
            $diff = array_diff($item, $fids);
            if ($diff) {
                $absent = array_merge($absent, $diff);
            }
        }
        return $absent;
    }

    /**
     * 根据fid批量获取家族信息
     * @param array $fidArr
     * @param array $fieldArr 查询的字段
     * @return array
     */
    public static function getFamilyBatchChounk($fidArr = [], $fieldArr = [])
    {
        if (empty($fidArr)) {
            return [];
        }
        if (!in_array('fid', $fieldArr)) {
            $fieldArr[] = 'fid';
        }
        $fidChunk = array_chunk($fidArr, 1000);
        $dataMap = [];
        foreach ($fidChunk as $fids) {
            $data = self::getListByWhere([
                ['fid', 'IN', $fids]
            ], implode(',', $fieldArr));
            if ($data) {
                foreach ($data as $v) {
                    $dataMap[$v['fid']] = $v;
                }
            }
        }
        return $dataMap;
    }
}