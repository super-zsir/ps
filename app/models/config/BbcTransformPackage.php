<?php

namespace Imee\Models\Config;

use Imee\Service\Helper;

class BbcTransformPackage extends BaseModel
{
    protected $allowEmptyStringArr = ['region'];

    public static function insertData($data)
    {
        try {
            $rec = self::useMaster()->findFirst(array(
                "keyword=:keyword:",
                "bind" => array("keyword" => $data['keyword'])
            ));

            if ($rec) {
                foreach ($data as $k => $v) {
                    $rec->{$k} = $v;
                }
                $d = $rec->save();
                if ($d) return true;
            } else {
                $rec = new self();
                foreach ($data as $k => $v) {
                    $rec->{$k} = $v;
                }
                $d = $rec->save();
                if ($d) return true;
            }
        } catch (\Exception $e) {
            return false;
        }
        return false;
    }

    public static function getPackageNameBatch($idArr = [], $fieldArr = ['id', 'package'])
    {
        if (empty($idArr)) {
            return [];
        }
        if (!in_array('id', $fieldArr)) {
            $fieldArr[] = 'id';
        }
        $colums = implode(',', $fieldArr);
        $ids = implode(',', $idArr);
        $data = Helper::fetch("select {$colums} from bbc_transform_package where id in ({$ids})", null, \ConfigBaseModel::SCHEMA);
        return array_column($data, null, 'id');
    }

}