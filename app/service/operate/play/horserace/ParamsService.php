<?php

namespace Imee\Service\Operate\Play\Horserace;

use Imee\Exception\ApiException;
use Imee\Service\Rpc\PsService;
use Imee\Service\Operate\Play\KvBaseService;
use Imee\Service\Operate\Play\GetKvConstant;
use Imee\Models\Xs\XsBigarea;

class ParamsService
{
    /** @var KvBaseService $kvService */
    private $kvService;

    /** @var int $engineId */
    private $engineId = XsBigarea::HORSE_RACE_A;

    public function __construct()
    {
        $this->kvService = new KvBaseService(
            GetKvConstant::KEY_HORSE_RACE_PARAMETERS,
            GetKvConstant::BUSINESS_TYPE_HORSE_RACE,
            null,
            'horseraceparams'
        );
    }

    public function getList(): array
    {
        return $this->kvService->getParamsList()['data'];
    }

    public function modify(array $params): array
    {
        $id = intval($params['id'] ?? 0);
        $name = $params['name'] ?? '';
        $weight = intval($params['weight'] ?? 0);

        $config = $this->validation($name, $weight);
        $data = [
            'config' => $config,
            'engine_id' => $this->engineId
        ];
        [$res, $msg] = (new PsService())->setHorseRaceConfig($data);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        return ['id' => $id, 'after_json' => $data];
    }

    public function validation(string $name, int $weight): array
    {
        if (empty($name) || empty($weight)) {
            throw new ApiException(ApiException::MSG_ERROR, '参数错误');
        }

        $config = $this->kvService->getRpcList();
        if ($name == 'after_percent') {
            if (!is_numeric($weight)) {
                throw new ApiException(ApiException::MSG_ERROR, '修改after时，数值必须为数字');
            }
        } else {
            if (filter_var($weight, FILTER_VALIDATE_INT) === false) {
                throw new ApiException(ApiException::MSG_ERROR, '数值必须为整数');
            }

            if ($name == 'hours' && ($weight < 1 || $weight > 1000)) {
                throw new ApiException(ApiException::MSG_ERROR, '数值为1-1000内数字');
            }

            if ($name == 'limit_loss_money' && $weight >= 0) {
                throw new ApiException(ApiException::MSG_ERROR, '修改loss时，数值必须为负数且为整数');
            }
        }

        $config[$name] = $name == 'after_percent' ? (float)$weight : (int)$weight;;
        $config['lastUpdateTime'] = time();

        return $config;
    }
}