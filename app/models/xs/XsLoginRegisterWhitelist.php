<?php

namespace Imee\Models\Xs;

class XsLoginRegisterWhitelist extends BaseModel
{
    // 名单类型
    const WHITE_TYPE = 0;
    const BLACk_MAC = 1;
    const BLACk_DID = 2;
    const BLACk_IP = 3;

    public static $typeMap = [
        self::WHITE_TYPE => 'mac白名单',
        self::BLACk_MAC  => 'mac黑名单',
        self::BLACk_DID  => 'did黑名单',
        self::BLACk_IP   => 'ip黑名单',
    ];

    public static function getListByObjectIdChunk(array $objectIdArr = [], $fields = 'object_id'): array
    {
        if (empty($objectIdArr)) {
            return [];
        }

        $objectIdChunk = array_chunk($objectIdArr,200);
        $dataMap = [];
        foreach ($objectIdChunk as $objectIds){
            $data = self::getListByWhere([
                ['object_id', 'in', $objectIds]
            ], $fields);
            if($data){
                foreach ($data as $v){
                    $dataMap[$v['object_id']] = $v;
                }
            }
        }
        return $dataMap;
    }
}