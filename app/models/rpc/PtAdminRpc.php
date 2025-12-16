<?php
/**
 * pt后台接口服务
 */

namespace Imee\Models\Rpc;

use GuzzleHttp\Psr7\Response;
use Imee\Comp\Common\Rpc\BaseRpc;

class PtAdminRpc extends BaseRpc
{
    const API_PUSH_INDEX = 'pushIndex'; // 投递待审核数据
    const API_SEARCH = 'search'; // 获取搜索条件下的数据列表
    const API_AUDIT = 'audit'; // 审核
    const API_PUSH_MULT_INDEX = 'pushMultIndex'; // 审核
    const API_CSMS_PUSH = 'push';                   // csms_push



    protected $apiDevConfig = [
        'domain' => 'http://192.168.82.100',
        'host'   => 'www.partying-new.cn'
    ];

    protected $apiConfig = [
        'domain' => 'https://partying-admin.aopacloud.sg',
        'host'   => 'partying-admin.aopacloud.sg'
    ];

    public $apiList = [
        self::API_PUSH_INDEX => [
            'path'   => '/api/open/csms/pushIndex',
            'method' => 'post',
        ],
        self::API_PUSH_MULT_INDEX => [
            'path'   => '/api/open/csms/pushMultIndex',
            'method' => 'post',
        ],
        self::API_SEARCH     => [
            'path'   => '/api/open/csms/search',
            'method' => 'post',
        ],
        self::API_AUDIT      => [
            'path'   => '/api/open/csms/audit',
            'method' => 'post',
        ],
        self::API_CSMS_PUSH => [
            'path'  => '/api/open/csms/push',
            'method' => 'post'
        ]
    ];

    protected function serviceConfig(): array
    {
        $config = ENV == 'dev' ? $this->apiDevConfig : $this->apiConfig;
        $config['options'] = [
            'headers'         => [
                'Content-Type'  => 'application/x-www-form-urlencoded',
                'Php-Auth-User' => 'csmsuser',
                'Php-Auth-Pw'   => 'C0kSvDGzx5BlgTol',
            ],
            'connect_timeout' => 5,
            'timeout'         => 10,
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