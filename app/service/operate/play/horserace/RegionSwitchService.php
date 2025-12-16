<?php

namespace Imee\Service\Operate\Play\Horserace;

use Imee\Service\Rpc\PsService;
use Imee\Service\Operate\Play\KvBaseService;
use Imee\Service\Operate\Play\GetKvConstant;
use Imee\Exception\ApiException;

class RegionSwitchService
{
    /** @var KvBaseService $kvService */
    private $kvService;

    /** @var PsService $service */
    private $service;

    public function __construct()
    {
        $this->service = new PsService();
        $this->kvService = new KvBaseService(
            GetKvConstant::KEY_HORSE_RACE_BIG_AREA_SWITCH,
            GetKvConstant::BUSINESS_TYPE_HORSE_RACE,
            GetKvConstant::INDEX_BIG_AREA_LIST,
            'horseraceswitch'
        );
    }

    public function getList(): array
    {
        return $this->kvService->getLevelAndReginList();
    }

    public function modify(array $params): array
    {
        $id = intval($params['big_area_id'] ?? 0);
        $status = intval($params['switch'] ?? -1);

        $list = array_column($this->getList()['data'], null,'big_area_id');
        $update = [
            'big_area_id' => $id,
            'switch'      => $status
        ];
        list($res, $msg) = $this->service->setHorseRaceSwitch($update);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        $beforeJson = [
            'big_area_id' => $id,
            'switch'      => $list[$id]['status'] ?? 0,
        ];
        
        return ['big_area_id' => $id, 'before_json' => $beforeJson, 'after_json' => $update];
    }
}