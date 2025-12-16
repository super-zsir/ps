<?php

namespace Imee\Models\Rpc;

use Imee\Comp\Common\Rpc\BaseRpc;

class OaRpc extends BaseRpc
{
    const LOGIN = 'login';
    const CREATE_ORDER = 'create_order';
    const ORDER_INFO = 'order_info';
    const UPLOAD_FILE = 'upload_file';
    const TEMPLATE_DETAIL = 'tpl_detail';

    protected $apiConfig = [
        'domain' => UC_DOMAIN,
        'host' => UC_HOST
    ];

    public $apiList = [
        self::LOGIN => [
            'path' => '/ucapi/external/v1/authApi/getToken',
            'method' => 'post',
        ],
        self::CREATE_ORDER => [
            'path' => '/ucapi/external/v1/xrxsApprovalApi/postApproval',
            'method' => 'post',
        ],
        self::ORDER_INFO => [
            'path' => '/ucapi/external/v1/xrxsApprovalApi/getApprovalStatus',
            'method' => 'post',
        ],
        self::UPLOAD_FILE => [
            'path' => '/ucapi/external/v1/xrxsApprovalApi/uploadFile',
            'method' => 'post',
        ],
        self::TEMPLATE_DETAIL => [
            'path' => '/ucapi/external/v1/xrxsApprovalApi/getTemplateDetail',
            'method' => 'post',
        ],
    ];

    protected function serviceConfig(): array
    {
        $config = $this->apiConfig;
        $config['options'] = [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'connect_timeout' => 30,
            'timeout' => 60,
            'curl' => [
                CURLOPT_CONNECTTIMEOUT => 30, // cURL连接超时（秒）
                CURLOPT_TIMEOUT        => 60, // cURL总超时（秒）
            ],
        ];

        $config['retry'] = [
            'max' => 1,
            'delay' => 10000,
        ];

        return $config;
    }

    protected function decode($response = null, $code = 200): array
    {
        if ($response) {
            return [json_decode($response->getBody(), true), $response->getStatusCode()];
        }

        return [null, 500];
    }

}