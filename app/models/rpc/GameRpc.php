<?php

namespace Imee\Models\Rpc;

use GuzzleHttp\Psr7\Response;
use Imee\Comp\Common\Rpc\BaseRpc;

/**
 * 使用说明：
 * $obj = new PsRpc();
 * 1.url传参
 * $obj->call(
 * PsRpc::API_PRICE_LEVEL, ['query' => []]
 * );
 * 2.json传参
 * $obj->call(
 * PsRpc::API_PRICE_LEVEL, ['json' => []]
 * );
 * 3.post x-www-form-urlencoded
 * $obj->call(
 * PsRpc::API_PRETTY_LIST, ['form_params' => []]
 * );
 * 4.post form-data
 * $obj->call(
 * PsRpc::API_PRETTY_LIST, ['multipart' => [
 * [
 * 'name'     => 'file',
 * 'contents' => $fileContent,
 * 'filename' => 'file_name.txt'
 * ],
 * [
 * 'name'     => 'test_name',
 * 'contents' => 'test_value'
 * ],
 * ]
 * );
 */

/**
 * 游戏中台接口配置
 */
class GameRpc extends BaseRpc
{
    // 游戏配置
    const API_GAME_CONFIG_LIST = 'game_config_list';
    const API_GAME_CONFIG_UPDATE = 'game_config_update';
    const API_GAME_CONFIG_DETAIL = 'game_config_detail';

    protected $apiDevConfig = [
        'domain' => 'http://172.16.1.64:9981',
        'host' => '172.16.1.64'
    ];

    protected $apiConfig = [
        'domain' => 'http://rpc-gateway.ps-app.private:9981',
        'host' => 'rpc-gateway.ps-app.private'
    ];

    public $apiList = [
        // 游戏配置列表
        self::API_GAME_CONFIG_LIST => [
            'path' => '/rpc/GameNode.ItemDist/GetConfigList',
            'method' => 'post',
        ],
        // 游戏配置编辑
        self::API_GAME_CONFIG_UPDATE => [
            'path' => '/rpc/GameNode.ItemDist/UpdateConfigData',
            'method' => 'post',
        ],
        // 游戏配置详情
        self::API_GAME_CONFIG_DETAIL => [
            'path' => '/rpc/GameNode.ItemDist/GetConfigDetail',
            'method' => 'post',
        ],
    ];

    protected function serviceConfig(): array
    {
        $config = ENV == 'dev' ? $this->apiDevConfig : $this->apiConfig;
        $config['options'] = [
            'headers' => [
                'Content-Type' => 'application/json',
                'X-RPCX-SerializeType' => 1,
            ],
            'connect_timeout' => 5,
            'timeout' => 10,
        ];

        $config['retry'] = [
            'max' => 1,
            'delay' => 100,
        ];

        return $config;
    }

    protected function decode(Response $response = null, $code = 200): array
    {
        if ($response) {
            return [json_decode($response->getBody(), true), $response->getStatusCode()];
        }

        return [null, 500];
    }
}