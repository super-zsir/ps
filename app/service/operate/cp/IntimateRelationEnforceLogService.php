<?php

namespace Imee\Service\Operate\Cp;

use Imee\Models\Xs\XsIntimateRelationEnforceLog;
use Imee\Service\StatusService;

class IntimateRelationEnforceLogService
{
    public function getListAndTotal(array $params): array
    {
        $limit = array_get($params, 'limit', 15);
        $page = array_get($params, 'page', 1);

        $id = intval(array_get($params, 'id', 0));
        $bigareaId = array_get($params, 'bigarea_id');
        $fromUid = array_get($params, 'from_uid');

        $query = [];
        $id && $query[] = ['id', '=', $id];
        $bigareaId && $query[] = ['bigarea_id', '=', $bigareaId];
        $fromUid && $query[] = ['from_uid', '=', $fromUid];

        $data = XsIntimateRelationEnforceLog::getListAndTotal($query, '*', 'id desc', $page, $limit);

        foreach ($data['data'] as &$rec) {
            $dateline = array_get($rec, 'dateline', 0);
            $relieveDateline = array_get($rec, 'relieve_dateline', 0);
            $rec['dateline'] = $dateline ? date('Y-m-d H:i:s', $dateline) : '';
            $rec['relieve_dateline'] = $relieveDateline ? date('Y-m-d H:i:s', $relieveDateline) : '';
        }

        return $data;
    }

    public static function getRelationTypeMap($value = null, string $format = '')
    {
        $map = XsIntimateRelationEnforceLog::$relationTypeMap;

        if (is_numeric($value)) {
            return $map[$value] ?? '';
        }
        if (!empty($format)) {
            $map = StatusService::formatMap($map, $format);
        }
        return $map;
    }
}