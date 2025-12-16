<?php

namespace Imee\Service\Operate\Cp;

use Imee\Exception\ApiException;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;

class RelationIncPropCardBuyRecordService
{
    /** @var PsService $rpc */
    private $rpc;

    public function __construct()
    {
        $this->rpc = new PsService();
    }

    public function getListAndTotal(array $params): array
    {
        $type = intval(array_get($params, 'type', 0));
        $bigAreaId = intval(array_get($params, 'bigarea_id', 0));
        $uid = intval(array_get($params, 'uid', 0));
        $startTime = array_get($params, 'dataline_sdate', 0);
        $endTime = array_get($params, 'dataline_edate', 0);
        $startTime = $startTime ? strtotime($startTime) : 0;
        $endTime = $endTime ? strtotime($endTime) : 0;

        $query = [
            'type' => $type,
            'page' => [
                'page_index' => intval($params['page'] ?? 1),
                'page_size'  => intval($params['limit'] ?? 15),
            ],
        ];

        $bigAreaId && $query['bigarea_id'] = $bigAreaId;
        $uid && $query['uid'] = $uid;
        $startTime && $query['start_time'] = $startTime;
        $endTime && $query['end_time'] = $endTime;

        list($res, $msg, $list) = $this->rpc->propCardBuyRecordList($query);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        foreach ($list['data'] as &$item) {
            $configName = @json_decode($item['config_name'], true);
            $item['config_info'] = $item['config_id'] . '-' . $configName['cn'];
            $item['dataline'] = Helper::now($item['dataline']);
        }

        return $list;
    }
}