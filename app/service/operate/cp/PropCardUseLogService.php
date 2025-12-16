<?php

namespace Imee\Service\Operate\Cp;

use Imee\Models\Xs\XsPropCardUseLog;
use Imee\Service\StatusService;

class PropCardUseLogService
{
    public function getListAndTotal(array $params): array
    {
        $limit = array_get($params, 'limit', 15);
        $page = array_get($params, 'page', 1);

        $id = intval(array_get($params, 'id', 0));
        $bigareaId = array_get($params, 'bigarea_id');
        $uid = array_get($params, 'uid');

        $query = [['card_type', '=', XsPropCardUseLog::CARD_TYPE_RELEASE]];
        $id && $query[] = ['id', '=', $id];
        $bigareaId && $query[] = ['bigarea_id', '=', $bigareaId];
        $uid && $query[] = ['uid', '=', $uid];

        $data = XsPropCardUseLog::getListAndTotal($query, '*', 'id desc', $page, $limit);
        foreach ($data['data'] as &$rec) {
            $extendData = @json_decode($rec['extend_data'] ?? '', true);
            $dateline = array_get($rec, 'dateline', 0);
            $bindDateline = array_get($extendData, 'bind_dateline', 0);
            $rec['dateline'] = $dateline ? date('Y-m-d H:i:s', $dateline) : '';
            $rec['bind_dateline'] = $bindDateline ? date('Y-m-d H:i:s', $bindDateline) : '';
            $rec['to_uid'] = array_get($extendData, 'to_uid', '');
        }
        return $data;
    }

    public static function getCardTypeMap($value = null, string $format = '')
    {
        $map = XsPropCardUseLog::$cardTypeMap;

        if (is_numeric($value)) {
            return $map[$value] ?? '';
        }
        if (!empty($format)) {
            $map = StatusService::formatMap($map, $format);
        }
        return $map;
    }
}