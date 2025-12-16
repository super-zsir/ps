<?php

namespace Imee\Service\Operate\Play\Luckyfruit;

use Imee\Exception\ApiException;
use Imee\Models\Xs\XsLuckyFruitsLimitConfig;
use Imee\Service\Rpc\PsService;
use Imee\Service\Helper;

class LuckyFruitsLimitConfigService
{

    /** @var PsService $rpc */
    private $rpc;

    public function __construct()
    {
        $this->rpc = new PsService();
    }

    public function getListAndTotal($params, $type = XsLuckyFruitsLimitConfig::CONFIG_TYPE_SINGLE): array
    {
        $limit = array_get($params, 'limit', 15);
        $page = array_get($params, 'page', 1);
        $id = array_get($params, 'id', 0);
        $ids = array_get($params, 'ids', []);
        $filterParams = [
            'config_type' => (int) $type,
            'page_num' => (int) $page,
            'page_size' => (int) $limit
        ];

        $id && $filterParams['id_list'] = [$id];
        $ids && $filterParams['id_list'] = $ids;
        list($res, $msg, $data) = $this->rpc->getLuckyFruitsLimitConfig($filterParams);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
        foreach ($data['data'] as &$rec) {
            $rec['rate_str'] = array_get($rec, 'rate', 0) . '%';
        }

        return $data;
    }

    public function add($params, $type = XsLuckyFruitsLimitConfig::CONFIG_TYPE_SINGLE): array
    {
        $data = $this->validateAndFormatData($params, $type);
        list($flg, $msg, $_id) = $this->rpc->luckyGiftLimitConfigAdd($data);
        if (!$flg) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        return ['id' => $_id, 'before_json' => [], 'after_json' => array_merge($data, ['id' => $_id])];
    }

    public function modify($params, $type = XsLuckyFruitsLimitConfig::CONFIG_TYPE_SINGLE): array
    {
        $id = array_get($params, 'id', 0);
        $config = $this->getListAndTotal(['id' => $id], $type)['data'][0] ?? [];
        if (empty($config)) {
            throw new ApiException(ApiException::MSG_ERROR, 'ID数据错误');
        }
        $update = $this->validateAndFormatData($params, $type);
        list($flg, $msg) = $this->rpc->luckyGiftLimitConfigEdit($update);
        if (!$flg) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        return ['id' => $id, 'before_json' => $config, 'after_json' => array_merge($config, $update)];
    }

    public function delete($params, $type = XsLuckyFruitsLimitConfig::CONFIG_TYPE_SINGLE): array
    {
        $id = array_get($params, 'id', 0);
        $config = $this->getListAndTotal(['id' => $id], $type)['data'][0] ?? [];
        if (empty($config)) {
            throw new ApiException(ApiException::MSG_ERROR, 'ID数据错误');
        }
        list($flg, $msg) = $this->rpc->luckyGiftLimitConfigDelete(['config_id_list' => [$id]]);
        if (!$flg) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        return ['id' => $id, 'before_json' => $config, 'after_json' => []];
    }
    
    public function deleteBatch($params, $type = XsLuckyFruitsLimitConfig::CONFIG_TYPE_SINGLE): array
    {
        $id = array_get($params, 'id', []);
        $id = Helper::formatIds($id);
        $realId = $this->getListAndTotal(['ids' => $id], $type)['data'];
        $realId = array_column($realId, 'id');
        if (count($realId) != count($id)) {
            $diffId = array_diff($id, $realId);
            throw new ApiException(ApiException::MSG_ERROR, sprintf("ID【%s】数据错误", implode(',', $diffId)));
        }
        list($flg, $msg) = $this->rpc->luckyGiftLimitConfigDelete(['config_id_list' => $realId]);
        if (!$flg) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        return ['id' => $id, 'before_json' => $params, 'after_json' => []];
    }

    public function validateAndFormatData($data, $type = XsLuckyFruitsLimitConfig::CONFIG_TYPE_SINGLE): array
    {
        $id = array_get($data, 'id', 0);
        $betMoney = array_get($data, 'bet_money', 0);
        $amount = array_get($data, 'amount', 0);
        $rate = array_get($data, 'rate', 0);

        if ($rate < 0 || $rate > 100) {
            throw new ApiException(ApiException::MSG_ERROR, '触发预期百分比范围为0-100');
        }

        $data = [
            'bet_money' => intval($betMoney),
            'amount' => intval($amount),
            'rate' => intval($rate),
        ];
        if (empty($id)) {
            $data['config_type'] = intval($type);
        } else {
            $data['config_id'] = intval($id);
        }
        return $data;
    }

}