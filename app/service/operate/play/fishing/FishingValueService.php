<?php

namespace Imee\Service\Operate\Play\Fishing;

use Imee\Exception\ApiException;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsGameRpcService;

class FishingValueService
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
            'page'      => intval($params['page'] ?? 1),
            'page_size' => intval($params['limit'] ?? 15),
        ];
        list($res, $msg, $data) = $this->rpcService->getFishValue($query);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        foreach ($data['data'] as &$item) {
            $item['percent_rate'] = $item['percent'] . '%';
        }

        return $data;
    }

    public function create(array $params): array
    {
        $data = $this->formatData($params);
        list($res, $msg) = $this->rpcService->addFishValue($data);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
        return ['id' => $msg, 'after_json' => $data];
    }

    public function modify(array $params): array
    {
        $data = $this->formatData($params);
        list($res, $msg) = $this->rpcService->editFishValue($data);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
        return ['id' => $params['id'], 'after_json' => $data];
    }

    private function formatData(array $params): array
    {
        $id = intval($params['id'] ?? 0);
        $date = intval(date('Ymd', time()));

        $data = [
            'contribute_value' => (int) $params['contribute_value'],
            'percent'          => intval($params['percent'] * 100),
            'type'             => (int)$params['type'],
            'update_dateline'  => $date,
            'editer'           => Helper::getAdminName($params['admin_uid'])
        ];

        if (empty($id)) {
            $data['create_dateline'] = $date;
        } else {
            $data['id'] = $id;
        }

        return $data;
    }
}