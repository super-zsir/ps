<?php

namespace Imee\Service\Operate\Play\Tarot;

use Imee\Exception\ApiException;
use Imee\Models\Xs\XsTarotContributionLimitConfig;
use Imee\Models\Xs\XsTarotOddsPercent;
use Imee\Service\Rpc\PsService;
use Imee\Service\StatusService;

class ValueService
{
    /**
     * @var PsService $rpcService
     */
    private $rpcService;

    public function __construct()
    {
        $this->rpcService = new PsService();
    }

    public function getList(array $params): array
    {
        $page = intval($params['page'] ?? 1);
        $limit = intval($params['limit'] ?? 15);

        $filterParams = [
            'page_num'    => $page,
            'page_size'   => $limit,
        ];

        list($res, $msg, $list) = $this->rpcService->getTarotContributionLimit($filterParams);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        foreach ($list['data'] as &$item) {
            $item['max_value'] = $item['max_value'] / 100;
            $item['percent'] = $item['percent'] / 100;
            $item['max_value_rate'] = $item['max_value'] . '%';
            $item['percent_rate'] = $item['percent'] . '%';
            $item['type'] = (string)$item['type'];
            $item['type_name'] = XsTarotOddsPercent::$typeMap[$item['type']] ?? '-';
        }

        return $list;
    }

    public function create(array $params): array
    {
        $data = $this->formatData($params);
        list($res, $msg) = $this->rpcService->addTarotContributionLimit($data);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        return ['after_json' => $data];
    }

    public function modify(array $params): array
    {
        $data = $this->formatData($params);
        list($res, $msg) = $this->rpcService->editTarotContributionLimit($data);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        return ['after_json' => $data];
    }

    private function formatData(array $params): array
    {
        return [
            'id'        => intval($params['id'] ?? 0),
            'value'     => intval($params['value']),
            'max_value' => intval($params['max_value'] * 100),
            'percent'   => intval($params['percent'] * 100),
            'type'      => intval($params['type'] ?? 0),
        ];
    }

    public function getTypeMap()
    {
        return StatusService::formatMap(XsTarotOddsPercent::$typeMap, 'label,value');
    }
}