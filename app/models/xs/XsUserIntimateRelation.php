<?php

namespace Imee\Models\Xs;

class XsUserIntimateRelation extends BaseModel
{
    protected static $primaryKey = 'id';

    public static function getObjBatchChounk($idArr = [], $fieldArr = ['id', 'objid_1', 'objid_2'])
    {
        if (empty($idArr)) {
            return [];
        }
        if (!in_array('id', $fieldArr)) {
            $fieldArr[] = 'id';
        }
        $idChunk = array_chunk($idArr,200);
        $dataMap = [];
        foreach ($idChunk as $ids){
            $data = self::getBatchCommon($ids, $fieldArr);
            if ($data) {
                foreach ($data as $item) {
                    $dataMap[$item['id']] = $item;
                }
            }

        }
        return $dataMap;
    }

    /**
     * 根据uids获取亲密关系列表
     * @param array $uids1
     * @param array $uids2
     * @return array
     */
    public static function getListByUids(array $uids1, array $uids2)
    {
        if (empty($uids1) || empty($uids2)) {
            return [];
        }

        $data = self::getListByWhere([
            ['objid_1', 'IN', $uids1],
            ['objid_2', 'IN', $uids2],
        ], 'id, objid_1, objid_2');

        if (empty($data)) {
            return [];
        }
        $map = [];

        foreach ($data as $item) {
            $uiqKey = $item['objid_1'] . '_' . $item['objid_2'];
            $map[$uiqKey] = $item['id'];
        }

        return $map;
    }
}