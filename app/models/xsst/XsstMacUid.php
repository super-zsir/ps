<?php

namespace Imee\Models\Xsst;

class XsstMacUid extends BaseModel
{
    public static function insertRows($data)
    {
        $rec = self::findFirst(array(
            "mac=:mac: and uid=:uid:",
            "bind" => array("mac" => $data["mac"], "uid" => $data["uid"])
        ));

        if ($rec) {
            return true;
        } else {
            $rec = new XsstMacUid();
            foreach ($data as $k => $v) {
                $rec->{$k} = $v;
            }
            $d = $rec->save();
            if ($d) return true;
        }
        return false;
    }

    public static function userMacSorted(array $uids): array
    {
        $data = self::getListByWhere([['uid', 'in', $uids], ['mac', '!=', '0000000000000000']], 'uid,mac,dateline');
        if (!$data) {
            return [];
        }

        $result = array_reduce(
            $data,
            function($carry, $item) {
                if (!isset($carry[$item['uid']])) {
                    $carry[$item['uid']] = [];
                }
                $carry[$item['uid']][] = $item;
                return $carry;
            },
            []
        );

        array_walk(
            $result,
            function(&$group) {
                usort(
                    $group,
                    function($a, $b) {
                        return $a['dateline'] <=> $b['dateline'];
                    }
                );
            }
        );

        return $result;
    }
}
