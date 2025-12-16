<?php

namespace Imee\Service\Rpc;

use Imee\Models\Rpc\GameRpc;

class GameRpcService
{
    /** @var GameRpc $rpc */
    private $rpc;

    public function __construct()
    {
        $this->rpc = new GameRpc();
    }

    public function getGameConfigList(array $params): array
    {
        list($res, $_) = $this->rpc->call(GameRpc::API_GAME_CONFIG_LIST, [
            'json' => [
                'dummy' => 1
            ],
        ]);
        if (isset($res['success']) && $res['success']) {
            return [true, '', $res['data']];
        }
        return [false, $res['msg'] ?? '接口错误', []];
    }

    public function getConfigDetail(int $id): array
    {
        list($res, $_) = $this->rpc->call(GameRpc::API_GAME_CONFIG_DETAIL, [
            'json' => [
                'poolID' => $id
            ],
        ]);
        if (isset($res['success']) && $res['success']) {
            return [true, '', $res['data']];
        }
        return [false, $res['msg'] ?? '接口错误', []];
    }

    public function updateConfigData(array $params): array
    {
        list($res, $_) = $this->rpc->call(GameRpc::API_GAME_CONFIG_UPDATE, [
            'json' => ['data' => $params],
            'headers' => [
                'X-RPCX-SerializeType' => 1,
            ],
        ]);
        if (isset($res['success']) && $res['success']) {
            return [true, ''];
        }
        return [false, $res['msg'] ?? '接口错误'];
    }
}