<?php

namespace Imee\Service\Operate\Play\Crash;

use Imee\Exception\ApiException;
use Imee\Models\Xs\XsGlobalConfig;
use Imee\Models\Xs\XsRocketCrashLimitConfig;
use Imee\Models\Xsst\BmsOperateLog;
use Imee\Models\Xsst\XsstRocketCrashOddsType;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;

class LimitConfigService
{
    /**
     * @var PsService $rpcService
     */
    private $rpcService;

    public function __construct()
    {
        $this->rpcService = new PsService();
    }

    public function getList(array $params): array
    {
        $page = intval($params['page'] ?? 1);
        $limit = intval($params['limit'] ?? 15);
        $configType = intval($params['config_type'] ?? 0);
        $id = intval($params['id'] ?? 0);

        $filterParams = [
            'page_num'    => $page,
            'page_size'   => $limit,
            'config_type' => $configType,
        ];

        $id && $filterParams['id'] = $id;

        list($res, $msg, $data) = $this->rpcService->getRocketCrashLimitConfig($filterParams);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
        $logs = BmsOperateLog::getFirstLogList($this->getGuidByType($params['config_type']), array_column($data['data'], 'id'));
        foreach ($data['data'] as &$v) {
            $v['percent_rate'] = $v['percent'] . '%';
            $v['high_percent_rate'] = $v['high_percent'] . '%';
            $v['jp_percent_rate'] = $v['jp_percent'] . '%';
            $v['admin'] = $logs[$v['id']]['operate_name'] ?? '-';
            $v['dateline'] = isset($logs[$v['id']]['created_time']) ? Helper::now($logs[$v['id']]['created_time']) : '';
        }
        return $data;
    }

    public function create(array $params): array
    {
        $data = $this->formatData($params);
        [$res, $msg] = $this->rpcService->addRocketCrashLimitConfig($data);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        return ['id' => $msg, 'after_json' => $data];
    }

    public function modify(array $params): array
    {
        $config = $this->getList(['id' => $params['id'], 'config_type' => $params['config_type']])['data'][0] ?? [];
        if (empty($config)) {
            throw new ApiException(ApiException::MSG_ERROR, '配置不存在');
        }
        $data = $this->formatData($params);
        [$res, $msg] = $this->rpcService->editRocketCrashLimitConfig($data);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        return ['id' => $params['id'], 'before_json' => $config, 'after_json' => $data];
    }

    public function getTypeMap(): array
    {
        $typeList = XsstRocketCrashOddsType::findAll();

        $map = [];

        if ($typeList) {
            foreach ($typeList as $item) {
                $map[] = [
                    'label' => $item['tid'],
                    'value' => $item['tid']
                ];
            }
        }

        return $map;
    }

    private function formatData(array $params): array
    {
        return [
            'config_type'  => (int)$params['config_type'],
            'value'        => (int)$params['value'],
            'percent'      => (int)$params['percent'],
            'high_percent' => intval($params['high_percent'] ?? 0),
            'jp_percent'   => intval($params['jp_percent'] ?? 0),
            'table_id'     => intval($params['table_id'] ?? 0),
            'id'           => intval($params['id'] ?? 0)
        ];
    }

    private function getGuidByType(int $type): string
    {
        $guid = '';
        switch ($type) {
            case XsRocketCrashLimitConfig::CRASH_TOTAL:
                $guid = 'crashtotal';
                break;
            case XsRocketCrashLimitConfig::CRASH_VALUE:
                $guid = 'crashvalue';
                break;
            case XsRocketCrashLimitConfig::CRASH_OVERTIME:
                $guid = 'crashovertime';
                break;
        }

        return $guid;
    }
}