<?php

namespace Imee\Models\Xsst;

class XsstCertificationLog extends BaseModel
{
    protected static $primaryKey = 'id';

    const AUDIT_STATE_DEFAULT = 0;
    const AUDIT_STATE_SUCCESS = 1;
    const AUDIT_STATE_ERROR = 2;

    public static function getAuditListByIds(array $ids, int $state, string $fields): array
    {
        $list = self::getListByWhere([
            ['id', 'in', $ids],
            ['state', '=', $state]
        ], $fields);
        return $list ? array_column($list, null, 'id') : [];
    }

    /**
     * 根据任务id+奖励下标获取添加数据
     * @param string $tidIndex
     * @return array
     */
    public static function getListByTidIndex(string $tidIndex): array
    {
        $recordList = XsstCertificationLog::getListByWhere([
            ['tid_index', '=', $tidIndex]
        ], 'id');

        return $recordList ? array_column($recordList, 'id') : [];
    }
}