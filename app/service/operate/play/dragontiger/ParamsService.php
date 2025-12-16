<?php

namespace Imee\Service\Operate\Play\Dragontiger;

use Imee\Exception\ApiException;
use Imee\Service\Rpc\PsService;
use Imee\Service\Operate\Play\KvBaseService;
use Imee\Service\Operate\Play\GetKvConstant;

class ParamsService
{
    /** @var KvBaseService $kvService */
    private $kvService;

    public function __construct()
    {
        $this->kvService = new KvBaseService(
            GetKvConstant::KEY_DRAGON_TIGER_PARAMETERS,
            GetKvConstant::BUSINESS_TYPE_DRAGON_TIGER,
            null,
            'dragontigerparams'
        );
    }

    public function getList(): array
    {
        return $this->kvService->getParamsList()['data'];
    }

    public function modify(array $params): array
    {
        $id = intval($params['id'] ?? 0);
        $key = $params['name'] ?? '';
        $weight = intval($params['weight'] ?? 0);
        
        $config = $this->validation($key, $weight);

        $beforeJson = [
            'id' => $id,
            $key => $config[$key]
        ];

        $config[$key] = $weight;
        [$res, $msg] = (new PsService())->setDragonTigerConfig($config);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
        return ['id' => $id, 'before_json' => $beforeJson, 'after_json' => [
            'id' => $id,
            $key => $config[$key]
        ]];
    }

    public function validation(string $key, int $weight): array
    {
        if (empty($key) || empty($weight)) {
            throw new ApiException(ApiException::MSG_ERROR, '参数错误');
        }

        $config = $this->kvService->getRpcList();
        if ($key == 'profit_line'
            && ($weight < 2000000
            || ($weight < ($config['profit_money'] * 2)))) {
                throw new ApiException(ApiException::MSG_ERROR, '利润分割线必须大于200万，不能小于利润分割金额的两倍');
        }

        if ($key == 'profit_money' 
            && ($weight > ($config['profit_line'] / 2))) {
            throw new ApiException(ApiException::MSG_ERROR, '利润分割金额不可超过利润分割线的50%');
        }


        if ($key == 'reward_upper_limit_rate' || $key == 'gold_finger_rate') {
            if ($weight > 10000) {
                throw new ApiException(ApiException::MSG_ERROR, '设置数值不可大于10000');
            }
        }
        
        return $config;
    }
}