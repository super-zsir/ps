<?php

namespace Imee\Service\Operate\Play\Pslot;

use Imee\Exception\ApiException;
use Imee\Service\Rpc\PsService;

class ValueService
{
    /**
     * @var PsService $rpcService
     */
    private $rpcService;

    private $percentFields = ['jp_percent', 'free_percent', 'jewel_percent', 'type_percent'];


    public function __construct()
    {
        $this->rpcService = new PsService();
    }

    public function getValueList(array $params): array
    {
        $query = [
            'page_num'  => $params['page'],
            'page_size' => $params['limit']
        ];
        list($res, $msg, $data) = $this->rpcService->querySlotContributionLimitConfig($query);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
        if (empty($data['data'])) {
            return $data;
        }

        foreach ($data['data'] as &$item) {
            foreach ($this->percentFields as $field) {
                $item[$field] = $item[$field] / 100;
                $item[$field . '_ratio'] = $item[$field] . '%';
            }
        }

        return $data;
    }

    public function setValue(array $params): array
    {
        $isTest = intval($params['is_test'] ?? 0);
        // 验证必要参数
        $type = $this->validateAndGetType($params);
        $id = intval($params['id'] ?? 0);

        // 构建配置数据
        $data = $this->buildConfigData($params, $type, $id);

        // 获取调用action
        $action = 'addSlotContributionLimitConfig';
        $id && $action = 'editSlotContributionLimitConfig';
        $requestParams = ['config' => $data];

        // 模拟接口参数不一样
        if ($isTest) {
            $action = 'simulateBet';
            $requestParams = ['limit_config' => $data];
        }
        list($res, $newId) = $this->rpcService->$action($requestParams);

        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, 'rpc error: ' . $newId);
        }

        return ['id' => $newId, 'after_json' => $requestParams];
    }

    private function validateAndGetType(array $params): int
    {
        $type = intval($params['type_id'] ?? -1);
        if ($type < 0) {
            throw new ApiException(ApiException::MSG_ERROR, 'type is required');
        }
        return $type;
    }

    private function buildConfigData(array $params, int $type, int $id): array
    {
        $data = [
            'contribute_value' => intval($params['contribute_value'] ?? 0),
            'type_id'          => $type,
            'id'               => $id
        ];

        // 处理百分比字段
        foreach ($this->percentFields as $field) {
            $data[$field] = ($params[$field] ?? 0) * 100;
        }

        return $data;
    }

    public function getTypeMap(): array
    {
        return [
            ['label' => 0, 'value' => 0],
            ['label' => 108, 'value' => 1],
            ['label' => 85, 'value' => 2],
            ['label' => 'free', 'value' => 3],
        ];
    }
}