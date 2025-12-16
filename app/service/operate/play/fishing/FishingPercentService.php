<?php

namespace Imee\Service\Operate\Play\Fishing;

use Imee\Exception\ApiException;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsGameRpcService;

class FishingPercentService
{
    /**
     * @var PsGameRpcService $rpcService
     */
    private $rpcService;

    public function __construct()
    {
        $this->rpcService = new PsGameRpcService();
    }

    public function getList(): array
    {
        list($res, $msg, $data) = $this->rpcService->getFishPercent();
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        foreach ($data as &$item) {
            $item['k0_rate'] = $item['k0'] . '%';
            $item['k1_rate'] = $item['k1'] . '%';
            $item['kz_rate'] = $item['kz'] . '%';
        }

        return $data;
    }

    public function modify(array $params): array
    {
        $data = $this->formatData($params);
        list($res, $msg) = $this->rpcService->editFishPercent($data);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
        return ['fishid' => Helper::arrayFilter($data, 'fishid'), 'after_json' => $data];
    }

    private function formatData(array $params): array
    {
        $fishPercentList = $params['fish_percent_list'] ?? [];

        $updatePercentData = [];
        foreach ($fishPercentList as $item) {
            $preg = '/^\d+(\.\d{1,2})?$/';
            if (!preg_match($preg, $item['k0']) || !preg_match($preg, $item['k1']) || !preg_match($preg, $item['kz'])) {
                throw new ApiException(ApiException::MSG_ERROR, 'type 修改错误，只允许修改为≥0的小数点后2位的数');
            }
            $updatePercentData[] = [
                'fishid'   => (int)$item['fishid'],
                'odds'     => (int)$item['odds'],
                'speed'    => (int)$item['speed'],
                'quality'  => (int)$item['quality'],
                'k0'       => intval($item['k0'] * 100),
                'k1'       => intval($item['k1'] * 100),
                'kz'       => intval($item['kz'] * 100),
                'bornrate' => (int)$item['bornrate'],
            ];
        }

        return $updatePercentData;
    }
}