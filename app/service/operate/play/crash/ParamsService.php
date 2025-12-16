<?php

namespace Imee\Service\Operate\Play\Crash;

use Imee\Exception\ApiException;
use Imee\Models\Xs\XsGlobalConfig;
use Imee\Models\Xsst\BmsOperateLog;
use Imee\Service\Helper;
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
            GetKvConstant::KEY_ROCKET_CRASH_PARAMETERS,
            GetKvConstant::BUSINESS_TYPE_ROCKET_CRASH,
            null,
            'crashparameters'
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

        $beforeJson = [$key => $config[$key] ?? 0];
        $config[$key] = $weight;
        [$res, $msg] = (new PsService())->setRocketCrashConfig($config);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
        return ['id' => $id, 'before_json' => $beforeJson, 'after_json' => [$key => $config[$key]]];
    }

    public function validation(string $key, int $weight): array
    {
        if (empty($key) || empty($weight)) {
            throw new ApiException(ApiException::MSG_ERROR, '参数错误');
        }

        $config = $this->kvService->getRpcList();
        if ($key == 'beginning_crash_percent' && $weight > 1000) {
            throw new ApiException(ApiException::MSG_ERROR, '数值为0-100内数字');
        }

        if ($key == 'hours' && $weight > 1000) {
            throw new ApiException(ApiException::MSG_ERROR, '数值为0-1000内数字');
        }
        if ($key == 'emoji_switch' && !in_array($weight, [0, 1])){
            throw new ApiException(ApiException::MSG_ERROR, '数值只能改为0或者1');
        }
        if ($key == 'jp_reward' && ($weight < 5 || $weight > 30)) {
            throw new ApiException(ApiException::MSG_ERROR, '数值为5-30正整数');
        }
        if ($key == 'ahead_off' && $weight < 0) {
            throw new ApiException(ApiException::MSG_ERROR, '数值为大于等于0的整数');
        }

        return $config;
    }
}