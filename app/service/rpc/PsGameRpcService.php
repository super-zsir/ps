<?php

namespace Imee\Service\Rpc;

use Imee\Models\Rpc\PsGameRpc;

class PsGameRpcService
{
    /** @var PsGameRpc $rpc */
    private $rpc;

    public function __construct()
    {
        $this->rpc = new PsGameRpc();
    }

    public function getFishList(): array
    {
        list($res, $_) = $this->rpc->call($this->rpc::API_GET_FISH_LIST, ['json' => []]);

        if (isset($res['code']) && $res['code'] == 200 && isset($res['msg']) && $res['msg'] == 'success') {
            return [true, '', $res['data']['fishList'] ?? []];
        }

        return [false, $res['msg'] ?? '接口错误', []];
    }

    public function upFishList(array $params): array
    {
        list($res, $_) = $this->rpc->call($this->rpc::API_UP_FISH_LIST, ['json' => $params]);

        if (isset($res['code']) && $res['code'] == 200 && isset($res['msg']) && $res['msg'] == 'success') {
            return [true, ''];
        }

        return [false, $res['msg'] ?? '接口错误'];
    }

    public function getFishPercent(): array
    {
        list($res, $_) = $this->rpc->call($this->rpc::API_GET_FISH_PERCENT, ['json' => []]);
        if (isset($res['code']) && $res['code'] == 200 && isset($res['msg']) && $res['msg'] == 'success') {
            return [true, '', $res['data']['fish_percent'] ?? []];
        }

        return [false, $res['msg'] ?? '接口错误', []];
    }

    public function editFishPercent(array $params): array
    {
        list($res, $_) = $this->rpc->call($this->rpc::API_EDIT_FISH_PERCENT, ['json' => $params]);

        if (isset($res['code']) && $res['code'] == 200 && isset($res['msg']) && $res['msg'] == 'success') {
            return [true, ''];
        }

        return [false, $res['msg'] ?? '接口错误'];
    }

    public function getTotalLimit(array $params): array
    {
        list($res, $_) = $this->rpc->call($this->rpc::API_GET_TOTAL_LIMIT, ['json' => $params]);
        if (isset($res['code']) && $res['code'] == 200 && isset($res['msg']) && $res['msg'] == 'success') {
            return [true, '', ['data' => $res['data']['total_limit'] ?? [], 'total' => $res['data']['count'] ?? 0]];
        }

        return [false, $res['msg'] ?? '接口错误', []];
    }

    public function addTotalLimit(array $params): array
    {
        list($res, $_) = $this->rpc->call($this->rpc::API_ADD_TOTAL_LIMIT, ['json' => $params]);

        if (isset($res['code']) && $res['code'] == 200 && isset($res['msg']) && $res['msg'] == 'success') {
            return [true, $res['data']['id'] ?? 0];
        }

        return [false, $res['msg'] ?? '接口错误'];
    }

    public function editTotalLimit(array $params): array
    {
        list($res, $_) = $this->rpc->call($this->rpc::API_EDIT_TOTAL_LIMIT, ['json' => $params]);

        if (isset($res['code']) && $res['code'] == 200 && isset($res['msg']) && $res['msg'] == 'success') {
            return [true, ''];
        }

        return [false, $res['msg'] ?? '接口错误'];
    }

    public function getFishValue(array $params): array
    {
        list($res, $_) = $this->rpc->call($this->rpc::API_GET_FISH_VALUE, ['json' => $params]);
        if (isset($res['code']) && $res['code'] == 200 && isset($res['msg']) && $res['msg'] == 'success') {
            return [true, '', ['data' => $res['data']['values'] ?? [], 'total' => $res['data']['count'] ?? 0]];
        }

        return [false, $res['msg'] ?? '接口错误', []];
    }

    public function addFishValue(array $params): array
    {
        list($res, $_) = $this->rpc->call($this->rpc::API_ADD_FISH_VALUE, ['json' => $params]);

        if (isset($res['code']) && $res['code'] == 200 && isset($res['msg']) && $res['msg'] == 'success') {
            return [true, $res['data']['id'] ?? 0];
        }

        return [false, $res['msg'] ?? '接口错误'];
    }

    public function editFishValue(array $params): array
    {
        list($res, $_) = $this->rpc->call($this->rpc::API_EDIT_FISH_VALUE, ['json' => $params]);

        if (isset($res['code']) && $res['code'] == 200 && isset($res['msg']) && $res['msg'] == 'success') {
            return [true, ''];
        }

        return [false, $res['msg'] ?? '接口错误'];
    }

    public function getFishParams(): array
    {
        list($res, $_) = $this->rpc->call($this->rpc::API_GET_FISH_PARAMS, ['json' => []]);
        if (isset($res['code']) && $res['code'] == 200 && isset($res['msg']) && $res['msg'] == 'success') {
            return [true, '', $res['data']['params'] ?? []];
        }

        return [false, $res['msg'] ?? '接口错误', []];
    }

    public function editFishParams(array $params): array
    {
        list($res, $_) = $this->rpc->call($this->rpc::API_EDIT_FISH_PARAMS, ['json' => $params]);
        if (isset($res['code']) && $res['code'] == 200 && isset($res['msg']) && $res['msg'] == 'success') {
            return [true, ''];
        }

        return [false, $res['msg'] ?? '接口错误'];
    }

    public function upRpc(): array
    {
        list($res, $_) = $this->rpc->call($this->rpc::API_UP_RPC, ['json' => []]);
        if (isset($res['code']) && $res['code'] == 200 && isset($res['msg']) && $res['msg'] == 'success') {
            return [true, ''];
        }

        return [false, $res['msg'] ?? '接口错误'];
    }


}