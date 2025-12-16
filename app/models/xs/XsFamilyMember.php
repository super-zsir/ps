<?php

namespace Imee\Models\Xs;

class XsFamilyMember extends BaseModel
{
    protected static $primaryKey = 'id';

    public static $role = [
        1 => '家族长',
        2 => '管理员',
        3 => '成员'
    ];

    public static function getMemberNumberByFid(array $fids): array
    {
        if (empty($fids)) {
            return [];
        }

        // Query the database to get all fid entries matching the fids array
        $res = self::find([
            'columns'    => 'fid',
            'conditions' => "fid in ({fids:array})",
            "bind"       => array("fids" => $fids),
        ])->toArray();

        // Calculate the count for each fid in PHP
        $countByFid = [];
        foreach ($res as $row) {
            $fid = $row['fid'];
            if (isset($countByFid[$fid])) {
                $countByFid[$fid]++;
            } else {
                $countByFid[$fid] = 1;
            }
        }

        return $countByFid;
    }

}