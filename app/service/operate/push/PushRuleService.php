<?php

namespace Imee\Service\Operate\Push;

use Imee\Exception\ApiException;
use Imee\Service\Helper;
use Imee\Service\Rpc\PushRpcService;

class PushRuleService
{
    /**
     * @var PushRpcService
     */
    private $rpcService;

    public function __construct()
    {
        $this->rpcService = new PushRpcService();
    }

    public function getList(array $params): array
    {
        $conditions = $this->getConditions($params);
        [$res, $msg, $data] = $this->rpcService->getPushRuleList($conditions);
        if (!$res) {
            return [false, $msg, []];
        }

        foreach ($data['list'] as &$item) {
            $planInfo = array_column($item['plan_info'],'plan_id');
            $item['plan_id'] = implode(',', $planInfo);
            $item['is_exclusion'] = $item['is_exclusion'] ?? 0;
            $item['status'] = $item['status'] ?? 2;
            $item['update_time'] = Helper::now($item['update_time'] ?? $item['create_time']);
        }

        return [true, '', $data];
    }

    public function add(array $params): array
    {
        $data = $this->formatParams($params);
        [$res, $msg, $id] = $this->rpcService->addPushRule($data);

        if (!$res) {
            return [false, $msg, []];
        }
        return [true, '', ['id' => $id, 'after_json' => $data]];
    }

    public function edit(array $params): array
    {
        $data = $this->formatParams($params);
        [$res, $msg, $id] = $this->rpcService->addPushRule($data);

        if (!$res) {
            return [false, $msg, []];
        }
        $data['id'] = $id;
        return [true, '', ['after_json' => $data]];
    }

    public function info(int $id)
    {
        $params = [
            'app_id' => APP_ID,
            'group_id' => $id,
            'page' => 1,
            'page_num' => 1
        ];
        [$res, $msg, $data] = $this->rpcService->getPushRuleList($params);
        if (!$res) {
            return [false, $msg, $data];
        }
        $info = $data['list'][0] ?? [];
        $info['member_data'] = [];
        foreach ($info['plan_info'] as $plan) {
            $info['member_data'][] = [
                'plan_id' => (string) $plan['plan_id'],
                'score'   => $plan['score'] ?? 0
            ];
        }
        return [true, '', $info];
    }

    public function status(int $id, int $status)
    {
        $adminId = Helper::getSystemUid();
        $admin = Helper::getAdminName($adminId);

        $data = [
            'group_id' => (int) $id,
            'app_id' => APP_ID,
            'status' => (int) $status,
            'modifier' => $admin
        ];
        [$res, $msg] = $this->rpcService->editPushRule($data);
        if (!$res) {
            return [false, $msg];
        }
        $data['id'] = $id;
        return [true, '', ['after_json' => $data]];
    }

    public function formatParams(array $params): array
    {
        $adminId = Helper::getSystemUid();
        $admin = Helper::getAdminName($adminId);
        $time = time();

        $data = [
            'group_data' => [
                'app_id' => APP_ID,
                'name'   => $params['name'],
                'modifier' => $admin,
                'update_time' => $time,
                'is_exclusion' => (int) $params['is_exclusion']
            ],
        ];
        if (isset($params['id']) && !empty($params['id'])) {
            $data['group_data']['group_id'] = (int) $params['id'];
        } else {
            $data['group_data']['creator'] = $admin;
            $data['group_data']['status'] = 1;
            $data['group_data']['create_time'] = $time;
        }
        if (count($params['member_data']) < 2) {
            throw new ApiException(ApiException::MSG_ERROR, '计划ID最少关联2个');
        }

        foreach ($params['member_data'] as &$item) {
            $item = array_map('intval', $item);
        }
        $data['member_data'] = $params['member_data'];
        return $data;
    }

    public function getPushPlanList()
    {
        $params= [
            'app_id' => APP_ID,
            'page' => 1,
            'page_num' => 5000,
            'mode' => 2
        ];
        [$res, $msg, $data] = $this->rpcService->getPushPlanList($params);
        $map = [];
        if (isset($data['list']) && !empty($data['list'])) {
            foreach ($data['list'] as $item) {
                $map[$item['id']] = $item['id'] . '-' . $item['name'];
            }
        }
        return $map;
    }

    private function getConditions(array $params)
    {
        $conditions = [
            'page' => (int)($params['page'] ?? 1),
            'page_num' => (int)($params['limit'] ?? 15),
            'app_id' => APP_ID,
        ];

        if (isset($params['name'])) {
            $conditions['group_name'] = $params['name'];
        }

        if (isset($params['status'])) {
            $conditions['status'] = (int)$params['status'];
        }

        if (isset($params['id'])) {
            $conditions['group_id'] = (int)$params['id'];
        }

        if (isset($params['plan_id'])) {
            $conditions['plan_id'] = (int)$params['plan_id'];
        }

        return $conditions;
    }
}