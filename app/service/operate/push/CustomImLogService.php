<?php

namespace Imee\Service\Operate\Push;

use Imee\Models\Xs\XsCustomImLog;

class CustomImLogService
{
    public function getListAndTotal(array $params): array
    {
        $limit = array_get($params, 'limit', 15);
        $page = array_get($params, 'page', 1);

        $id = intval(array_get($params, 'id', 0));
        $uid = intval(array_get($params, 'uid', 0));
        $bigArea = intval(array_get($params, 'big_area', 0));
        $start = trim(array_get($params, 'dateline_sdate', ''));
        $end = trim(array_get($params, 'dateline_edate', ''));

        $query = [];
        $id && $query[] = ['id', '=', $id];
        $uid && $query[] = ['uid', '=', $uid];
        $bigArea && $query[] = ['big_area', '=', $bigArea];
        $start && $query[] = ['dateline', '>=', strtotime($start . ' 00:00:00')];
        $end && $query[] = ['dateline', '<=', strtotime($end . ' 23:59:59')];

        $data = XsCustomImLog::getListAndTotal($query, '*', 'id desc', $page, $limit);

        foreach ($data['data'] as &$rec) {
            $dateline = array_get($rec, 'dateline', 0);
            $rec['dateline'] = $dateline ? date('Y-m-d H:i:s', $dateline) : '';
        }
        return $data;
    }
}