<?php

namespace Imee\Models\Rpc;

use GuzzleHttp\Psr7\Response;
use Imee\Comp\Common\Rpc\BaseRpc;

class PushRpc extends BaseRpc
{
    // push内容
    const API_PUSH_CONTENT_LIST = 'push_content_list';
    const API_PUSH_CONTENT_ADD = 'push_content_add';
    const API_PUSH_CONTENT_EDIT = 'push_content_edit';
    const API_PUSH_CONTENT_DEL = 'push_content_del';
    const API_PUSH_CONTENT_INFO = 'push_content_info';

    // push计划
    const API_PUSH_PLAN_LIST = 'push_plan_list';
    const API_PUSH_PLAN_ADD = 'push_plan_add';
    const API_PUSH_PLAN_EDIT = 'push_plan_edit';
    const API_PUSH_PLAN_DEL = 'push_plan_del';
    const API_PUSH_PLAN_INFO = 'push_plan_info';
    const API_PUSH_PLAN_STOP = 'push_plan_stop';

    const API_PUSH_RECORD_LIST = 'push_record_list';

    const API_PUSH_RULE_LIST = 'push_rule_list';
    const API_PUSH_RULE_ADD = 'push_rule_add';
    const API_PUSH_RULE_EDIT = 'push_rule_edit';

    protected $apiDevConfig = [
        'domain' => 'http://47.114.166.11:6080',
        'host' => '47.114.166.11'
    ];

    protected $apiConfig = [
        'domain' => 'http://bc-rpc-gateway.aopacloud.private:9981',
        'host' => 'bc-rpc-gateway.aopacloud.private'
    ];

    public $apiList = [
        self::API_PUSH_CONTENT_LIST => [
            'path' => '/rpc/BC.Push/PushContentSearch',
            'method' => 'post',
        ],
        self::API_PUSH_CONTENT_ADD => [
            'path' => '/rpc/BC.Push/PushContentAdd',
            'method' => 'post',
        ],
        self::API_PUSH_CONTENT_EDIT => [
            'path' => '/rpc/BC.Push/PushContentUpd',
            'method' => 'post',
        ],
        self::API_PUSH_CONTENT_DEL => [
            'path' => '/rpc/BC.Push/PushContentDel',
            'method' => 'post',
        ],
        self::API_PUSH_CONTENT_INFO => [
            'path' => '/rpc/BC.Push/PushContentGet',
            'method' => 'post',
        ],
        self::API_PUSH_PLAN_LIST => [
            'path' => '/rpc/BC.Push/PushPlanSearch',
            'method' => 'post',
        ],
        self::API_PUSH_PLAN_ADD => [
            'path' => '/rpc/BC.Push/PushPlanAdd',
            'method' => 'post',
        ],
        self::API_PUSH_PLAN_EDIT => [
            'path' => '/rpc/BC.Push/PushPlanUpd',
            'method' => 'post',
        ],
        self::API_PUSH_PLAN_DEL => [
            'path' => '/rpc/BC.Push/PushPlanDel',
            'method' => 'post',
        ],
        self::API_PUSH_PLAN_STOP => [
            'path' => '/rpc/BC.Push/PushStop',
            'method' => 'post',
        ],
        self::API_PUSH_PLAN_INFO => [
            'path' => '/rpc/BC.Push/PushPlanGet',
            'method' => 'post',
        ],
        self::API_PUSH_RECORD_LIST => [
            'path' => '/rpc/BC.Push/PushRecordSearch',
            'method' => 'post',
        ],
        self::API_PUSH_RULE_LIST => [
            'path' => '/rpc/BC.Push/GetTeamMember',
            'method' => 'post',
        ],
        self::API_PUSH_RULE_ADD => [
            'path' => '/rpc/BC.Push/CreateTeam',
            'method' => 'post',
        ],
        self::API_PUSH_RULE_EDIT => [
            'path' => '/rpc/BC.Push/UpGroup',
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