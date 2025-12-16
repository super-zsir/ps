<?php

namespace Imee\Service\Rpc;

use Imee\Models\Rpc\PushRpc;

class PushRpcService
{
    /** @var PushRpc $rpc */
    private $rpc;

    public function __construct()
    {
        $this->rpc = new PushRpc();
    }

    public function getPushContentList(array $params): array
    {
        $data = [
            'app_id' => APP_ID,
            'title' => $params['title'] ?? '',
            'id' => (int) ($params['id'] ?? 0),
            'status' => (int) ($params['status'] - 1),
            'page' => (int) ($params['page'] ?? 1),
            'page_num' => (int) $params['limit'] ?? 15,
        ];
        [$res, $code] = $this->rpc->call(PushRpc::API_PUSH_CONTENT_LIST, [
            'json' => $data
        ]);

        if (isset($res['success']) && $res['success']) {
            $list = [
                'total' => $res['total'] ?? 0,
                'list'  => $res['content_list'] ?? []
            ];
            return [true, '', $list];
        }


        return [false, $res['msg'] ?? '接口错误', []];
    }

    public function addPushContent(array $data): array
    {
        [$res, $_] = $this->rpc->call(PushRpc::API_PUSH_CONTENT_ADD, ['json' => [
            'content' => $data
        ]]);
        if (isset($res['success']) && $res['success']) {
            return [true, '', $res['body']['id'] ?? 0];
        }
        return [false, $res['msg'] ?? '接口错误', ''];
    }

    public function editPushContent(array $data): array
    {
        [$res, $_] = $this->rpc->call(PushRpc::API_PUSH_CONTENT_EDIT, ['json' => [
            'content' => $data
        ]]);
        if (isset($res['success']) && $res['success']) {
            return [true, ''];
        }
        return [false, $res['msg'] ?? '接口错误'];
    }

    public function delPushContent(int $id): array
    {
        [$res, $_] = $this->rpc->call(PushRpc::API_PUSH_CONTENT_DEL, ['json' => [
            'id' => (int) $id
        ]]);
        if (isset($res['success']) && $res['success']) {
            return [true, ''];
        }
        return [false, $res['msg'] ?? '接口错误'];
    }

    public function getPushContent(int $id): array
    {
        [$res, $_] = $this->rpc->call(PushRpc::API_PUSH_CONTENT_INFO, ['json' => [
            'id' => $id
        ]]);

        if (isset($res['success']) && $res['success']) {
            return [true, '', $res['content']];
        }
        return [false, '接口错误', []];
    }

    public function getPushPlanList(array $params): array
    {
        [$res, $code] = $this->rpc->call(PushRpc::API_PUSH_PLAN_LIST, [
            'json' => $params
        ]);

        if (isset($res['success']) && $res['success']) {
            $list = [
                'total' => $res['total'] ?? 0,
                'list'  => $res['plan_list'] ?? []
            ];
            return [true, '', $list];
        }


        return [false, $res['msg'] ?? '接口错误', []];
    }

    public function addPushPlan(array $data): array
    {
        [$res, $_] = $this->rpc->call(PushRpc::API_PUSH_PLAN_ADD, ['json' => [
            'plan' => $data
        ]]);
        if (isset($res['success']) && $res['success']) {
            return [true, '', $res['body']['id'] ?? 0];
        }
        return [false, $res['msg'] ?? '接口错误', ''];
    }

    public function editPushPlan(array $data): array
    {
        [$res, $_] = $this->rpc->call(PushRpc::API_PUSH_PLAN_EDIT, ['json' => [
            'plan' => $data
        ]]);
        if (isset($res['success']) && $res['success']) {
            return [true, ''];
        }
        return [false, $res['msg'] ?? '接口错误'];
    }

    public function delPushPlan(int $id): array
    {
        [$res, $_] = $this->rpc->call(PushRpc::API_PUSH_PLAN_DEL, ['json' => [
            'id' => (int) $id
        ]]);
        if (isset($res['success']) && $res['success']) {
            return [true, ''];
        }
        return [false, $res['msg'] ?? '接口错误'];
    }

    public function stopPushPlan(int $id): array
    {
        [$res, $_] = $this->rpc->call(PushRpc::API_PUSH_PLAN_STOP, ['json' => [
            'plan_id' => (int) $id
        ]]);
        if (isset($res['success']) && $res['success']) {
            return [true, ''];
        }
        return [false, $res['msg'] ?? '接口错误'];
    }

    public function getPushPlan(int $id): array
    {
        [$res, $_] = $this->rpc->call(PushRpc::API_PUSH_PLAN_INFO, ['json' => [
            'id' => (int) $id
        ]]);
        if (isset($res['success']) && $res['success']) {
            return [true, '', $res['plan'] ?? []];
        }
        return [false, $res['msg'] ?? '接口错误', []];
    }

    public function getPushRecordList(array $params): array
    {
        $data = [
            'plan_id' =>  (int) ($params['id'] ?? 0),
            'page' => (int) ($params['page'] ?? 1),
            'page_num' => (int) ($params['limit'] ?? 15),
        ];
        [$res, $code] = $this->rpc->call(PushRpc::API_PUSH_RECORD_LIST, [
            'json' => $data
        ]);
        if (isset($res['success']) && $res['success']) {
            $list = [
                'total' => $res['total'] ?? 0,
                'list'  => $res['record_list'] ?? []
            ];
            return [true, '', $list];
        }


        return [false, $res['msg'] ?? '接口错误', []];
    }

    public function getPushRuleList(array $params): array
    {
        [$res, $code] = $this->rpc->call(PushRpc::API_PUSH_RULE_LIST, [
            'json' => $params
        ]);
        if (isset($res['success']) && $res['success']) {
            $list = [
                'total' => $res['body']['total'] ?? 0,
                'list'  => $res['body']['data'] ?? []
            ];
            return [true, '', $list];
        }


        return [false, $res['msg'] ?? '接口错误', []];
    }

    public function addPushRule(array $data): array
    {
        [$res, $_] = $this->rpc->call(PushRpc::API_PUSH_RULE_ADD, [
            'json' => $data
        ]);
        if (isset($res['success']) && $res['success']) {
            return [true, '', $res['body']['id'] ?? 0];
        }
        return [false, $res['msg'] ?? '接口错误', ''];
    }

    public function editPushRule(array $data): array
    {
        [$res, $_] = $this->rpc->call(PushRpc::API_PUSH_RULE_EDIT, [
            'json' => $data
        ]);
        if (isset($res['success']) && $res['success']) {
            return [true, ''];
        }
        return [false, $res['msg'] ?? '接口错误'];
    }
}