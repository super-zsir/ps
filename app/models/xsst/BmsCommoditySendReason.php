<?php
/**
 * 物品发放审核不通过原因
 */

namespace Imee\Models\Xsst;

class BmsCommoditySendReason extends BaseModel
{
    protected static $primaryKey = 'id';

    public static $reason = '物品未审核通过';

    public static function getReason(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }
        $data = self::find([
            'columns'    => 'sid,reason',
            'conditions' => 'sid IN({ids:array})',
            'bind'       => ['ids' => array_values($ids)]
        ])->toArray();
        if (!empty($data)) {
            $data = array_column($data, 'reason', 'sid');
        }
        return $data;
    }

    public static function setFailReason(int $id): bool
    {
        $rec = self::findFirst([
            'conditions' => 'sid=:id:',
            'bind'       => compact('id'),
        ]);
        if (!$rec) {
            $rec = new self();
        }
        $rec->sid = $id;
        $rec->reason = self::$reason;
        $rec->update_time = time();
        $d = $rec->save();
        return !!$d;
    }

    public static function getByReason(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }
        $data = self::find([
            'conditions' => 'sid IN({ids:array}) AND reason=:reason:',
            'bind'       => ['ids' => $ids, 'reason' => self::$reason]
        ])->toArray();
        return is_array($data) ? $data : [];
    }

    public static function cancelReason(int $sid): bool
    {
        if ($sid < 1) {
            return false;
        }
        $rec = self::findFirst([
            'conditions' => 'sid=:sid:',
            'bind'       => compact('sid')
        ]);
        if (!$rec) return false;
        $rec->reason = '';
        $rec->update_time = time();
        $d = $rec->save();
        return !!$d;
    }
}