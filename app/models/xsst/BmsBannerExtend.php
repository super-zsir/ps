<?php


namespace Imee\Models\Xsst;


class BmsBannerExtend extends BaseModel
{
    protected static $primaryKey = 'bid';

    public static function BmsBannerExtend($idArr = [], $fieldArr = ['bid', 'note'], $columns = null)
    {
        if (empty($idArr)) {
            return [];
        }
        if (!in_array('bid', $fieldArr)) {
            $fieldArr[] = 'bid';
        }

        $data = self::find(array(
            'columns'    => implode(',', $fieldArr),
            'conditions' => "bid in ({bid:array})",
            'bind'       => array(
                'bid' => $idArr,
            ),
        ))->toArray();
        if (empty($data)) {
            return array();
        }
        return array_column($data, $columns, 'bid');
    }

    public static function saveRow(int $bannerId, string $note): array
    {
        if (!$note) {
            return [true, ''];
        }
        $rec = self::findOneByWhere([['bid', '=', $bannerId]]);
        if (!empty($rec)) {
            if ($rec['note'] != $note) {
                return self::edit($rec['bid'], [
                    'note'        => $note,
                    'update_time' => time(),
                ]);
            }
        } else {
            return self::add([
                'bid'         => $bannerId,
                'note'        => $note,
                'create_time' => time(),
            ]);
        }
        return [true, ''];
    }
}