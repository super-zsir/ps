<?php

namespace Imee\Service\Operate\Play\Luckyfruit;

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
            GetKvConstant::KEY_LUCKY_FRUITS_BIG_AREA_SWITCH,
            GetKvConstant::BUSINESS_TYPE_LUCKY_FRUIT,
            GetKvConstant::INDEX_BIG_AREA_LIST,
            'luckyfruitregionswitch'
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

        list($res, $msg) = (new PsService())->setLuckyFruitsSwitch($update);
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