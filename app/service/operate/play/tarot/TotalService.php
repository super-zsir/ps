<?php

namespace Imee\Service\Operate\Play\Tarot;

use Imee\Exception\ApiException;
use Imee\Service\Rpc\PsService;

class TotalService
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

        list($res, $msg, $list) = $this->rpcService->getTarotTotalLimitConfig($filterParams);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        foreach ($list['data'] as &$item) {
            $item['value'] = $item['value'] / 100;
            $item['cheat_percent'] = $item['cheat_percent'] / 100;
            $item['jp_percent'] = $item['jp_percent'] / 100;
            $item['cheat_percent_rate'] = $item['cheat_percent'] . '%';
            $item['jp_percent_rate'] = $item['jp_percent'] . '%';
        }

        return $list;
    }

    public function create(array $params): array
    {
        $data = $this->formatData($params);
        list($res, $msg) = $this->rpcService->addTarotTotalLimitConfig($data);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        return ['after_json' => $data];
    }

    public function modify(array $params): array
    {
        $data = $this->formatData($params);
        list($res, $msg) = $this->rpcService->editTarotTotalLimitConfig($data);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        return ['after_json' => $data];
    }

    private function formatData(array $params): array
    {
        return [
            'id'            => intval($params['id'] ?? 0),
            'value'         => intval($params['value'] * 100),
            'cheat_percent' => intval((float)($params['cheat_percent'] ?? 0) * 100),
            'jp_percent'    => intval(($params['jp_percent'] ?? 0) * 100),
        ];
    }
}