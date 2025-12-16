<?php

namespace Imee\Service\Operate\Play\Dragontiger;

use Imee\Models\Xs\XsGlobalConfig;
use Imee\Models\Xsst\BmsOperateLog;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;
use Imee\Service\Operate\Play\KvBaseService;
use Imee\Service\Operate\Play\GetKvConstant;
use Imee\Exception\ApiException;

class OddsService
{
    /** @var KvBaseService $kvService */
    private $kvService;

    private $index = GetKvConstant::INDEX_DRAGON_TIGER_CONFIG;

    public function __construct()
    {
        $this->kvService = new KvBaseService(
            GetKvConstant::KEY_DRAGON_TIGER_PARAMETERS,
            GetKvConstant::BUSINESS_TYPE_DRAGON_TIGER,
            null,
            'dragontigerodds'
        );
    }

    public function getList(): array
    {
        $list = $this->kvService->getRpcList();
        $data = [];
        foreach ($list[$this->index] as $item) {
            $data[] = [
                'id' => $item['DRAGON_TIGER_ID'],
                'name' => XsGlobalConfig::$dragonTigerConfig[$item['DRAGON_TIGER_ID']],
                'hit_rate' => $item['hit_rate'],
            ];
        }
        $logs = BmsOperateLog::getFirstLogList('dragontigerodds', Helper::arrayFilter($data, 'id'));
        foreach ($data as &$v) {
            $v['admin_name'] = $logs[$v['id']]['operate_name'] ?? '-';
            $v['dateline'] = isset($logs[$v['id']]['created_time']) ? Helper::now($logs[$v['id']]['created_time']) : '';
        }

        return $data;
    }

    public function modify(array $params): array
    {
        $id = intval($params['id'] ?? 0);
        $rate = intval($params['hit_rate'] ?? 0);

        if (empty($id) || empty($rate)) {
            throw new ApiException(ApiException::MSG_ERROR, '参数错误');
        }

        $config = $this->kvService->getRpcList();
        $beforeJson = [];
        foreach ($config[$this->index] as &$v) {
            if ($v['DRAGON_TIGER_ID'] == $id) {
                $beforeJson = [
                    'DRAGON_TIGER_ID' => $id,
                    'hit_rate' => $v['hit_rate']
                ];
                $v['hit_rate'] = $rate;
            }
        }
        [$res, $msg] = (new PsService())->setDragonTigerConfig($config);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
        return ['id' => $id, 'before_json' => $beforeJson, 'after_json' => [
            'DRAGON_TIGER_ID' => $id,
            'hit_rate'        => $rate
        ]];
    }
}