<?php

namespace Imee\Service\Operate\Play\Fishing;

use Imee\Exception\ApiException;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsGameRpcService;

class FishingTotalService
{
    /**
     * @var PsGameRpcService $rpcService
     */
    private $rpcService;

    public function __construct()
    {
        $this->rpcService = new PsGameRpcService();
    }

    public function getList(array $params): array
    {
        $query = [
            'page' => intval($params['page'] ?? 1),
            'page_size' => intval($params['limit'] ?? 15),
        ];

        list($res, $msg, $data) = $this->rpcService->getTotalLimit($query);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
        foreach ($data['data'] as &$item) {
            $item['total_value_rate'] = $item['total_value'] . '%';
            $item['percent_rate'] = $item['percent'] . '%';
        }

        return $data;
    }

    public function create(array $params): array
    {
        $data = $this->formatData($params);
        list($res, $msg) = $this->rpcService->addTotalLimit($data);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
        return ['id' => $msg, 'after_json' => $data];
    }

    public function modify(array $params): array
    {
        $data = $this->formatData($params);
        list($res, $msg) = $this->rpcService->editTotalLimit($data);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
        return ['id' => $params['id'], 'after_json' => $data];
    }

    private function formatData(array $params): array
    {
        return [
            'id'          => intval($params['id'] ?? 0),
            'total_value' => intval($params['total_value'] * 100),
            'percent'     => intval($params['percent'] * 100),
            'type'        => (int)$params['type'],
            'updateAt'    => intval(date('Ymd', time())),
            'editer'      => Helper::getAdminName($params['admin_uid'])
        ];
    }
}