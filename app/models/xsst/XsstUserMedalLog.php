<?php

namespace Imee\Models\Xsst;

use Imee\Service\Helper;

class XsstUserMedalLog extends BaseModel
{
    public static $primaryKey = 'id';

    const SEND_TYPE = 1;
    const LESS_TIME_TYPE = 2;

    public static function getUserMedalLogBatch(array $ids, int $type = 1, string $fields = '*'): array
    {
        $table = self::getTableName();
        if (empty($ids)) return [];
        $ids = implode(',', $ids);
        $sql = "SELECT {$fields} FROM {$table} WHERE id IN (
    				SELECT MAX(id) FROM {$table} WHERE `type`={$type} AND sid IN({$ids}) GROUP BY sid
    			)";
        $logs = Helper::fetch($sql, null, BaseModel::SCHEMA);
        if (empty($logs)) return [];
        return array_column($logs, null, 'sid');
    }

    public static function getUserMedalLog(int $medalId, int $uid, string $fields = '*'): array
    {
        $res = self::findFirst([
            'columns' => $fields,
            'conditions' => "uid = :uid: AND medal_id = :medal_id:",
            'bind' => [
                'uid' => $uid,
                'medal_id' => $medalId
            ],
            'order' => 'id desc',
        ]);
        if (!$res) {
            return [];
        }
        return $res->toArray();
    }

    public static function addLogBatch($data)
    {
        return self::addBatch($data);
    }
}