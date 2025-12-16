<?php

namespace Imee\Service\Operate\Play\Fishing;

use Imee\Exception\ApiException;
use Imee\Service\Rpc\PsGameRpcService;

class FishingParamsService
{
    /**
     * @var PsGameRpcService $rpcService
     */
    private $rpcService;

    // 固定下各个key的id
    private $keyIdMap = [
        'X'           => 1,
        'M%'          => 2,
        'after'       => 3,
        '1weight'     => 4,
        '2weight'     => 5,
        '3weight'     => 6,
        'Time'        => 7,
        '2Time'       => 8,
        '3Time'       => 9,
        'bulletspeed' => 10,
        'seat'        => 11,
    ];

    public function __construct()
    {
        $this->rpcService = new PsGameRpcService();
    }

    public function getList(): array
    {
        list($res, $msg, $data) = $this->rpcService->getFishParams();
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
        $list = [];
        foreach ($data as $key => $value) {
            $list[] = [
                'id'         => $this->keyIdMap[$key],
                'key'        => $key,
                'value'      => $value,
                'value_rate' => in_array($key, ['M%', 'after']) ? ($value . '%') : $value
            ];
        }

        return array_sort($list, 'id');
    }

    public function modify(array $params): array
    {
        $data = $this->validate($params);
        list($res, $msg) = $this->rpcService->editFishParams($data);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        return ['id' => $params['id'], 'after_json' => $data];
    }

    private function validate(array $params): array
    {
        $key = $params['key'];
        $value = intval($params['value']);

        switch ($key) {
            case 'X':
                if ($value < 0 || $value > 1000) {
                    throw new ApiException(ApiException::MSG_ERROR, 'Value必须为0~1000内数字');
                }
                break;
            case 'M%':
            case '1weight':
            case '2weight':
            case '3weight':
            case 'Time':
            case '2Time':
            case '3Time':
            case 'seat':
                if ($value < 1) {
                    throw new ApiException(ApiException::MSG_ERROR, 'Value必须为正整数');
                }
                break;
            case 'after':
                if ($value < 0 || $value > 100) {
                    throw new ApiException(ApiException::MSG_ERROR, 'Value必须为0~100内数字');
                }
                break;
        }

        if (in_array($key, ['M%', 'after'])) {
            $value = $value * 100;
        }

        return [
            'key'   => $key,
            'value' => $value
        ];
    }
}