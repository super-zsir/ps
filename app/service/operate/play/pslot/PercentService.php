<?php

namespace Imee\Service\Operate\Play\Pslot;

use Imee\Exception\ApiException;
use Imee\Models\Xsst\BmsOperateLog;
use Imee\Service\Rpc\PsService;

class PercentService
{
    /**
     * @var PsService $rpcService
     */
    private $rpcService;


    public function __construct()
    {
        $this->rpcService = new PsService();
    }

    /**
     * 获取percent列表
     * @param int $type
     * @return array
     * @throws ApiException
     */
    private function getRpcList(int $type): array
    {
        // 获取权重数据
        list($success, $message, $data) = $this->rpcService->querySlotItemWeight([
            'type_id' => $type
        ]);

        if (!$success) {
            throw new ApiException(ApiException::MSG_ERROR, 'rpc error:' . $message);
        }

        return $data;
    }

    public function getPercentList(array $params): array
    {
        $typeId = intval($params['list_tab_key'] ?? 0);

        $data = $this->getRpcList($typeId);

        if (empty($data)) {
            return $data;
        }

        // 获取操作日志
        $operateLogs = BmsOperateLog::getFirstLogList('pslotpercent', [$typeId]);
        $operateName = ($operateLogs[$typeId] ?? [])['operate_name'] ?? '';
        // 处理权重数据
        $formattedData = [];
        foreach ($data as $item) {
            $itemId = $item['item_id'];
            if (!isset($formattedData[$itemId])) {
                $formattedData[$itemId] = $this->initializeFormattedData($itemId, $operateName);
            }

            $columnId = $item['column_id'];
            $formattedData[$itemId]["column_id_{$columnId}"] = intval($item['weight'] / 100);
            $formattedData[$itemId]["column_id_{$columnId}_id"] = $item['id'];
        }

        return array_values($formattedData);
    }

    private function initializeFormattedData(int $itemId, string $admin): array
    {
        $formattedData = [
            'item_id' => $itemId,
            'admin'   => $admin
        ];

        // 初始化所有column_id相关字段
        for ($i = 1; $i <= 5; $i++) {
            $formattedData["column_id_{$i}"] = 0;
            $formattedData["column_id_{$i}_id"] = 0;
        }

        return $formattedData;
    }

    public function modifyPercent(array $params, bool $isTest = false): array
    {
        $type = intval($params['list_tab_key'] ?? -1);
        if ($type < 0) {
            throw new ApiException(ApiException::MSG_ERROR, 'type is required');
        }
        $update = [];
        $pattern = '/^column_id_(\d+)_(\d+)_(\d+)$/';

        foreach ($params as $key => $value) {
            // 跳过非权重相关字段
            if (!str_contains($key, 'column_id_') || !is_numeric($value)) {
                continue;
            }

            if (!preg_match($pattern, $key, $matches)) {
                continue;
            }
            $columnId = (int)$matches[1];
            $itemId = (int)$matches[2];
            $id = (int)$matches[3];

            // 验证参数合法性
            if ($columnId < 1 || $columnId > 5 || $itemId < 1 || $id < 1) {
                continue;
            }

            // 验证权重值
            $weight = intval($value) * 100;
            if ($weight < 0) {
                throw new ApiException(ApiException::MSG_ERROR, "weight must be greater than or equal to 0");
            }

            $update[] = [
                'id'        => $id,
                'type_id'   => $type,
                'item_id'   => $itemId,
                'column_id' => $columnId,
                'weight'    => $weight
            ];
        }

        if (!$isTest && empty($update)) {
            throw new ApiException(ApiException::MSG_ERROR, 'update data is empty');
        }

        $action = 'editSlotItemWeight';
        $requestParams = ['item_list' => $update];

        // 模拟接口时只需要传type_id
        if ($isTest) {
            $action = 'simulateBet';
            $update = $this->setSimulateBetData($update, $type);
        }


        list($res, $msg) = $this->rpcService->$action(['item_list' => $update]);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, 'rpc error:' . $msg);
        }

        return ['list_tab_key' => $type, 'after_json' => $requestParams];
    }

    /**
     * 重置percent值
     * @param array $update
     * @param int $type
     * @return array
     * @throws ApiException
     */
    private function setSimulateBetData(array $update, int $type): array
    {
        $data = $this->getRpcList($type);

        if (empty($data)) {
            return $data;
        }

        $percentList = array_column($data, null, 'id');
        $newUpdate = array_column($update, null, 'id');

        foreach ($percentList as $id => &$item) {
            !empty($newUpdate[$id]) && $item = $newUpdate[$id];
        }

        return array_values($percentList);
    }
}