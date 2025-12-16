<?php

namespace Imee\Service\Operate\Play\Horserace;

use Imee\Exception\ApiException;
use Imee\Models\Xs\XsGlobalConfig;
use Imee\Models\Xs\XsTarotContributionLimitConfig;
use Imee\Models\Xs\XsTarotOddsPercent;
use Imee\Service\Rpc\PsService;
use Imee\Service\StatusService;

class ValueService
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
        list($res, $msg, $list) = $this->rpcService->getHorseValueList($params);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
        if (empty($list)) {
            return $list;
        }
        foreach ($list['data'] as &$item) {
            $item['percent'] = $item['percent'] / 100;
            $item['percent_rate'] = $item['percent'] . '%';
            $item['cheat_percent'] = $item['cheat_percent'] / 100;
            $item['cheat_percent_rate'] = $item['cheat_percent'] . '%';
        }

        return $list;
    }

    public function create(array $params): array
    {
        $data = $this->formatData($params);
        list($res, $msg) = $this->rpcService->addHorseValue($data);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        return ['id' => $msg, 'after_json' => $data];
    }

    public function modify(array $params): array
    {
        $data = $this->formatData($params);
        list($res, $msg) = $this->rpcService->editHorseValue($data);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        return ['id' => $data['id'], 'after_json' => $data];
    }

    private function formatData(array $params): array
    {
        return [
            'id'               => intval($params['id'] ?? 0),
            'game_id'          => XsGlobalConfig::GAME_CENTER_ID_HORSE_RACE,
            'contribute_value' => intval($params['contribute_value']),
            'percent'          => intval($params['percent'] * 100),
            'cheat_percent'    => intval($params['cheat_percent'] * 100),
        ];
    }

    public function getTypeMap()
    {
        return StatusService::formatMap(XsTarotOddsPercent::$typeMap, 'label,value');
    }
}