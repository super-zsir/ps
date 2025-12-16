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
 * PS游戏中台接口配置
 */
class PsGameRpc extends BaseRpc
{
    const API_GET_FISH_LIST = 'get_fish_list';
    const API_UP_FISH_LIST = 'up_fis_list';

    const API_GET_FISH_PERCENT = 'get_fish_percent';
    const API_EDIT_FISH_PERCENT = 'edit_fish_percent';

    const API_GET_TOTAL_LIMIT = 'get_total_limit';
    const API_ADD_TOTAL_LIMIT = 'add_total_limit';
    const API_EDIT_TOTAL_LIMIT = 'edit_total_limit';

    const API_GET_FISH_VALUE = 'get_fish_value';
    const API_ADD_FISH_VALUE = 'add_fish_value';
    const API_EDIT_FISH_VALUE = 'edit_fish_value';

    const API_GET_FISH_PARAMS = 'get_fish_params';
    const API_EDIT_FISH_PARAMS = 'edit_fish_params';

    const API_UP_RPC = 'up_rpc';

    protected $apiDevConfig = [
        'domain' => 'http://8.219.12.184:8081',
        'host'   => '8.219.12.184'
    ];

    protected $apiConfig = [
        'domain' => 'http://10.32.146.218:1999',
        'host'   => '10.32.146.218'
    ];

    public $apiList = [
        self::API_GET_FISH_LIST     => [
            'path'   => 'config/getFishList',
            'method' => 'get',
        ],
        self::API_UP_FISH_LIST      => [
            'path'   => 'config/upFishList',
            'method' => 'post',
        ],
        self::API_GET_FISH_PERCENT  => [
            'path'   => 'config/getFishPercent',
            'method' => 'get',
        ],
        self::API_EDIT_FISH_PERCENT => [
            'path'   => 'config/editFishPercent',
            'method' => 'post',
        ],
        self::API_GET_TOTAL_LIMIT   => [
            'path'   => 'config/getTotalLimit',
            'method' => 'get',
        ],
        self::API_ADD_TOTAL_LIMIT   => [
            'path'   => 'config/addTotalLimit',
            'method' => 'post',
        ],
        self::API_EDIT_TOTAL_LIMIT  => [
            'path'   => 'config/editTotalLimit',
            'method' => 'post',
        ],
        self::API_GET_FISH_VALUE    => [
            'path'   => 'config/getFishValue',
            'method' => 'get',
        ],
        self::API_ADD_FISH_VALUE    => [
            'path'   => 'config/addFishValue',
            'method' => 'post',
        ],
        self::API_EDIT_FISH_VALUE   => [
            'path'   => 'config/editFishValue',
            'method' => 'post',
        ],
        self::API_GET_FISH_PARAMS   => [
            'path'   => 'config/getFishParams',
            'method' => 'get',
        ],
        self::API_EDIT_FISH_PARAMS  => [
            'path'   => 'config/editFishParams',
            'method' => 'post',
        ],
        self::API_UP_RPC            => [
            'path'   => 'config/upRpc',
            'method' => 'get',
        ],
    ];

    protected function serviceConfig(): array
    {
        $config = ENV == 'dev' ? $this->apiDevConfig : $this->apiConfig;
        $config['options'] = [
            'headers'         => [
                'Content-Type'         => 'application/json',
                'X-RPCX-SerializeType' => 1,
            ],
            'connect_timeout' => 5,
            'timeout'         => 10,
        ];

        $config['retry'] = [
            'max'   => 1,
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