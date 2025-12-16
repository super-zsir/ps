<?php

namespace Imee\Service\Operate\Play;

use Imee\Exception\ApiException;
use Imee\Models\Xsst\BmsOperateLog;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsGameRpcService;
use Imee\Service\Rpc\PsService;
use Imee\Models\Xs\XsGlobalConfig;

class KvBaseService
{
    /**
     * @var PsService $rpcService
     */
    private $rpcService;

    /**
     * @var PsGameRpcService $gameRpcService
     */
    private $gameRpcService;

    private $_key;
    private $_businessType;
    private $_index;
    private $_guid;

    const KEY = '';
    const INDEX = '';
    const BUSINESS_TYPE = '';
    const GUID = '';

    public function __construct($key = null, $businessType = null, $index = null, $guid = null)
    {
        $this->_key = $this->getKey($key);
        $this->_businessType = $this->getBusinessType($businessType);
        $this->_index = $this->getIndex($index);
        $this->_guid = $this->getGuid($guid);
        $this->rpcService = new PsService();
        $this->gameRpcService = new PsGameRpcService();
    }

    /**
     * GetKv List
     * @return array
     * @throws ApiException
     */
    public function getRpcList(): array
    {
        list($res, $msg, $data) = $this->rpcService->getKv([
            'key'           => $this->_key,
            'business_type' => $this->_businessType,
        ]);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
        $list = json_decode($data['data'][0]['value'] ?? '', true) ?? [];
        if (empty($list)) {
            throw new ApiException(ApiException::MSG_ERROR, '获取配置失败');
        }

        return $this->_index ? ($list[$this->_index] ?? []) : $list;
    }

    public function getLevelAndReginList(): array
    {
        $list = $this->getRpcList();
        $logs = BmsOperateLog::getFirstLogList($this->_guid, Helper::arrayFilter($list, 'big_area_id'));
        foreach ($list as &$v) {
            $v['switch'] = strval($v['switch'] ?? 0);
            $v['global_rank_switch'] = strval($v['global_rank_switch'] ?? 0);
            $v['big_area_id'] = (string)$v['big_area_id'];
            $v['admin_name'] = $logs[$v['big_area_id']]['operate_name'] ?? '-';
            $v['dateline'] = isset($logs[$v['big_area_id']]['created_time']) ? Helper::now($logs[$v['big_area_id']]['created_time']) : '';
        }

        return ['data' => $list, 'total' => count($list)];
    }

    public function getParamsList(): array
    {
        $list = $this->getRpcList();
        $data = [];
        [$paramsIdMap, $paramsNameMap] = $this->getParamsMap();
        foreach ($paramsIdMap as $key => $val) {
            if (is_string($key)) {
                $weight = $list[$key] ?? 0;
                $data[] = [
                    'id'      => $val,
                    'name'    => $key,
                    'cn_name' => $paramsNameMap[$key],
                    'number'  => in_array($key, ['after_percent', 'get_jp_rate']) ? $weight . '%' : $weight,
                    'weight'  => $weight
                ];
            }
        }
        $logs = BmsOperateLog::getFirstLogList($this->_guid, Helper::arrayFilter($data, 'id'));
        foreach ($data as &$v) {
            $v['admin_name'] = $logs[$v['id']]['operate_name'] ?? '-';
            $v['dateline'] = isset($logs[$v['id']]['created_time']) ? Helper::now($logs[$v['id']]['created_time']) : '';
        }

        return ['data' => $data, 'total' => count($data)];
    }

    private function getParamsMap(): array
    {
        $params = GetKvConstant::PARAMS_FIELDS[$this->_businessType];
        $idMap = $nameMap = [];
        foreach ($params as $val) {
            $idMap[$val] = GetKvConstant::TAROT_PARAMS_ID[$val];
            $nameMap[$val] = GetKvConstant::TAROT_PARAMS_NAME[$val];
        }

        return [$idMap, $nameMap];
    }

    public function getTotalList(array $params): array
    {
        $query = [
            'game_id'   => $params['game_id'],
            'page_num'  => $params['page'],
            'page_size' => $params['limit']
        ];
        list($res, $msg, $data) = $this->rpcService->queryGameTotalLimitConfigList($query);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
        if (empty($data['data'])) {
            return $data;
        }
        foreach ($data['data'] as &$item) {
            $item['value'] = $item['total_value'] / 100;
            $item['cheat_percent'] = $item['cheat_percent'] / 100;
            $item['jp_percent'] = $item['jp_percent'] / 100;
            $item['cheat_percent_rate'] = $item['cheat_percent'] . '%';
            $item['jp_percent_rate'] = $item['jp_percent'] . '%';
        }

        return $data;
    }

    public function getValueList(array $params): array
    {
        $query = [
            'game_id'   => $params['game_id'],
            'page_num'  => $params['page'],
            'page_size' => $params['limit']
        ];
        list($res, $msg, $data) = $this->rpcService->queryGameContributionLimitConfigList($query);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
        if (empty($data['data'])) {
            return $data;
        }
        foreach ($data['data'] as &$item) {
            $item['value'] = $item['contribute_value'];
            $item['max_value'] = $item['percent'] / 100;
            $item['percent'] = $item['cheat_percent'] / 100;
            $item['max_value_rate'] = $item['max_value'] . '%';
            $item['percent_rate'] = $item['percent'] . '%';
        }

        return $data;
    }

    public function setLevel(array $params): array
    {
        $data = [
            'big_area_id' => (int)$params['big_area_id'],
            'limit_level' => (int)$params['limit_level'],
        ];
        // 需要把json整体查出来，替换掉修改的部分，在传给服务端
        $list = $this->getRpcList();
        foreach ($list as &$item) {
            $item['big_area_id'] == $data['big_area_id'] && $item = $data;
        }
        $this->setKv($list);
        $this->syncFishingConfig();
        return ['after_json' => $data];
    }

    public function setRegion(array $params): array
    {
        $data = [
            'big_area_id'        => (int)$params['big_area_id'],
            'switch'             => (int)$params['switch'],
            'global_rank_switch' => (int)($params['global_rank_switch'] ?? 0),
        ];

        // 需要把json整体查出来，替换掉修改大区开关的部分，在传给服务端
        $list = $this->getRpcList();
        foreach ($list as &$item) {
            $item['big_area_id'] == $data['big_area_id'] && $item = $data;
        }
        $this->setKv($list);
        $this->syncFishingConfig();

        return ['after_json' => $data];
    }

    public function setParams(array $params): array
    {
        // 验证权重值
        $this->validateWeight($params['name'], $params['weight']);

        // 获取并更新配置
        $list = $this->getRpcList();
        $list[$params['name']] = $this->formatWeight($params['name'], $params['weight']);
        $this->setKv($list);

        return ['after_json' => $list];
    }

    private function validateWeight(string $name, $weight): void
    {
        switch ($name) {
            case 'robot_switch':
                if ($weight < 0 || $weight > 1) {
                    throw new ApiException(ApiException::MSG_ERROR, '修改AI时，数值必须为0-1之间的数');
                }
                break;

            case 'after_percent':
                if (!is_numeric($weight)) {
                    throw new ApiException(ApiException::MSG_ERROR, '修改after时，数值必须为数字');
                }
                break;

            case 'first_three':
                if (!preg_match('/^[0-9]+(\.[0-9]{1,2})?$/', $weight)) {
                    throw new ApiException(ApiException::MSG_ERROR, '修改first_three时，数值必须为数字，且小数点后最多两位');
                }
                break;

            case 'jp_add_ratio':
                if (!preg_match('/^\d+(\.\d?)?$/', $weight)) {
                    throw new ApiException(ApiException::MSG_ERROR, '修改jp_add_ratio时，数值必须为正数，且小数点后最多一位');
                }
                break;

            default:
                if (filter_var($weight, FILTER_VALIDATE_INT) === false) {
                    throw new ApiException(ApiException::MSG_ERROR, '数值必须为整数');
                }

                if ($name == 'get_jp_rate' && ($weight < 0 || $weight > 100)) {
                    throw new ApiException(ApiException::MSG_ERROR, '修改get_jp_rate时，数值必须为0-100之间的数');
                }

                if ($name == 'hours' && $weight > 1000) {
                    throw new ApiException(ApiException::MSG_ERROR, '数值为0-1000内数字');
                }

                if ($name == 'limit_loss_money' && $weight >= 0) {
                    throw new ApiException(ApiException::MSG_ERROR, '修改loss时，数值必须为负数且为整数');
                }
        }
    }

    private function formatWeight(string $name, $weight)
    {
        if ($name === 'robot_switch') {
            return round($weight, 2);
        }

        return in_array($name, ['after_percent', 'jp_add_ratio', 'first_three'])
            ? (double)$weight
            : (int)$weight;
    }

    public function setTotal(array $params): array
    {
        $data = [
            'id'            => intval($params['id'] ?? 0),
            'game_id'       => $params['game_id'],
            'total_value'   => intval($params['value'] * 100),
            'cheat_percent' => intval(((float)$params['cheat_percent'] ?? 0) * 100),
            'jp_percent'    => intval(($params['jp_percent'] ?? 0) * 100),
        ];
        if ($data['id']) {
            list($res, $msg) = $this->rpcService->editGameTotalLimitConfig($data);
        } else {
            list($res, $msg) = $this->rpcService->addGameTotalLimitConfig($data);
        }
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
        return ['id' => $msg ?: $data['id'], 'after_json' => $data];
    }

    public function setValue(array $params): array
    {
        $data = [
            'id'               => intval($params['id'] ?? 0),
            'game_id'          => $params['game_id'],
            'contribute_value' => intval($params['value']),
            'percent'          => intval($params['max_value'] * 100),
            'cheat_percent'    => intval($params['percent'] * 100),
            'type'             => intval($params['type'] ?? 0),
        ];

        if ($data['id']) {
            list($res, $msg) = $this->rpcService->editGameContributionLimitConfig($data);
        } else {
            list($res, $msg) = $this->rpcService->addGameContributionLimitConfig($data);
        }
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
        return ['id' => $msg ?: $data['id'], 'after_json' => $data];
    }

    public function setKv($list)
    {
        $data = $this->_index ? [$this->_index => $list] : $list;
        list($res, $msg) = (new PsService())->setKv([
            'value'         => $data,
            'key'           => $this->_key,
            'business_type' => $this->_businessType,
        ]);

        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
    }

    private function syncFishingConfig(): void
    {
        if ($this->_businessType == GetKvConstant::BUSINESS_TYPE_FISHING) {
            list($res, $msg) = $this->gameRpcService->upRpc();
            if (!$res) {
                throw new ApiException(ApiException::MSG_ERROR, 'fishing配置同步失败，失败原因：' . $msg);
            }
        }
    }

    protected function getKey($key)
    {
        if ($key) {
            return $key;
        }

        return static::KEY;
    }

    protected function getBusinessType($businessType)
    {
        if ($businessType) {
            return $businessType;
        }

        return static::BUSINESS_TYPE;
    }

    protected function getIndex($index)
    {
        if ($index) {
            return $index;
        }

        return static::INDEX;
    }

    protected function getGuid($guid)
    {
        if ($guid) {
            return $guid;
        }

        return static::GUID;
    }
}