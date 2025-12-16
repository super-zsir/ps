<?php

namespace Imee\Service\Operate\Play\Crash;

use Imee\Service\Rpc\PsService;
use Imee\Service\Operate\Play\KvBaseService;
use Imee\Service\Operate\Play\GetKvConstant;
use Imee\Exception\ApiException;

class RegionSwitchService
{
    /** @var KvBaseService $kvService */
    private $kvService;
    public function __construct()
    {
        $this->kvService = new KvBaseService(
            GetKvConstant::KEY_ROCKET_CRASH_BIG_AREA_SWITCH,
            GetKvConstant::BUSINESS_TYPE_ROCKET_CRASH,
            GetKvConstant::INDEX_BIG_AREA_LIST,
            'crashregionswitch'
        );
    }

    public function getList(): array
    {
        return $this->kvService->getLevelAndReginList();
    }

    public function modify(array $params): array
    {
        $id = intval($params['big_area_id'] ?? 0);
        $switch = intval($params['switch'] ?? 0);
        $globalSwitch = intval($params['global_rank_switch'] ?? 0);

        $list = array_column($this->getList()['data'], null,'big_area_id');
        $update = [
            'big_area_id'        => $id,
            'switch'             => $switch,
            'global_rank_switch' => $globalSwitch,
        ];

        list($res, $msg) = (new PsService())->setRocketCrashSwitch($update);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
        $beforeJson = [
            'big_area_id'        => $id,
            'switch'             => $list[$id]['switch'] ?? 0,
            'global_rank_switch' => $list[$id]['global_rank_switch'] ?? 0,
        ];

        return ['big_area_id' => $id, 'before_json' => $beforeJson, 'after_json' => $update];
    }
}