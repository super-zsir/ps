<?php

namespace Imee\Service\Operate\Play\Horserace;

use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xs\XsGlobalConfig;
use Imee\Models\Xsst\BmsOperateLog;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;
use Imee\Service\Operate\Play\KvBaseService;
use Imee\Service\Operate\Play\GetKvConstant;
use Imee\Exception\ApiException;

class WeightService
{
    /** @var KvBaseService $kvService */
    private $kvService;

    /** @var string $index */
    private $index = GetKvConstant::INDEX_HORSE_RACE_CONFIG;

    /** @var int $engineId */
    private $engineId = XsBigarea::HORSE_RACE_A;

    public function __construct()
    {
        $this->kvService = new KvBaseService(
            GetKvConstant::KEY_HORSE_RACE_PARAMETERS,
            GetKvConstant::BUSINESS_TYPE_HORSE_RACE,
            null,
            'horseraceweight'
        );
    }

    public function getList(): array
    {
        $list = $this->kvService->getRpcList();
        $data = [];
        foreach ($list[$this->index] as $item) {
            $data[] = [
                'id' => $item['id'],
                'name' => XsGlobalConfig::$horseRaceMap[$item['id']],
                'hit_rate' => $item['hit_rate'],
            ];
        }
        $logs = BmsOperateLog::getFirstLogList('horseraceweight', [$this->engineId]);
        foreach ($data as &$v) {
            $v['admin_name'] = $logs[$this->engineId]['operate_name'] ?? '-';
            $v['dateline'] = isset($logs[$this->engineId]['created_time']) ? Helper::now($logs[$this->engineId]['created_time']) : '';
        }

        return $data;
    }

    public function modify(array $params): array
    {
        $list = $this->kvService->getRpcList();
        $horseConfig = $params['horse_config'] ?? [];
        if (empty($horseConfig)) {
            throw new ApiException(ApiException::MSG_ERROR, '配置不能为空');
        }
        $horseConfig = array_column($horseConfig, 'hit_rate', 'id');
        foreach($list['horse_config'] as &$item) {
            $item['hit_rate'] = (int) $horseConfig[$item['id']];
        }
        $list['lastUpdateTime'] = time();
        $data = [
            'config'    => $list,
            'engine_id' => $this->engineId
        ];
        [$res, $msg] = (new PsService())->setHorseRaceConfig($data);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
        return ['id' => $this->engineId, 'after_json' => $params['horse_config']];
    }
}