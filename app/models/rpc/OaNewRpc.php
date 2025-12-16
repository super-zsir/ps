<?php

namespace Imee\Models\Rpc;

use Imee\Comp\Common\Rpc\BaseRpc;

class OaNewRpc extends BaseRpc
{
    const LOGIN = 'login';
    const CREATE_ORDER = 'create_order';
    const CREATE_ORDER_INST = 'form_inst';
    const ORDER_INFO = 'order_info';
    const UPLOAD_FILE = 'upload_file';
    const TEMPLATE_PARAMS = 'tpl_params';

    protected $apiDevConfig = [
        'domain' => 'http://192.168.11.121:8864',
        'host' => '192.168.11.121'
    ];

    protected $apiConfig = [
        'domain' => 'https://uc.aopacloud.sg',
        'host' => 'uc.aopacloud.sg'
    ];

    public $apiList = [
        self::LOGIN => [
            'path' => '/ucapi/external/v1/authApi/getToken',
            'method' => 'post',
        ],
        self::CREATE_ORDER => [
            'path' => '/ucapi/external/v1/ap2rovalApis/createInst',
            'method' => 'post',
        ],
        self::ORDER_INFO => [
            'path' => '/ucapi/external/v1/ap2rovalApis/getFlowStatus',
            'method' => 'post',
        ],
        self::UPLOAD_FILE => [
            'path' => '/ucapi/external/v1/ap2rovalApis/uploadFile',
            'method' => 'post',
        ],
        self::TEMPLATE_PARAMS => [
            'path' => '/ucapi/external/v1/ap2rovalApis/getViewFormDef',
            'method' => 'post',
        ],
        self::CREATE_ORDER_INST => [
            'path' => '/ucapi/external/v1/ap2rovalApis/getViewFormInst',
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
            'connect_timeout' => 20,
            'timeout' => 30,
        ];

        $config['retry'] = [
            'max' => 0,
            'delay' => 100,
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