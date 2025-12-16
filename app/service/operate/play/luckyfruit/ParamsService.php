<?php

namespace Imee\Service\Operate\Play\Luckyfruit;

use Imee\Comp\Common\Log\Service\OperateLog;
use Imee\Models\Xsst\BmsOperateLog;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;
use Imee\Service\Operate\Play\KvBaseService;
use Imee\Service\Operate\Play\GetKvConstant;
use Imee\Exception\ApiException;

class ParamsService
{
    /** @var KvBaseService $kvService */
    private $kvService;

    public function __construct()
    {
        $this->kvService = new KvBaseService(
            GetKvConstant::KEY_LUCKY_FRUIT_PARAMETERS,
            GetKvConstant::BUSINESS_TYPE_LUCKY_FRUIT,
            null,
            'luckyfruitparams'
        );
    }
    public function getList(): array
    {
        $list = $this->kvService->getParamsList()['data'];
        foreach($list as &$item) {
            $item['cn_name'] = GetKvConstant::LUCKY_FRUIT_PARAMS_NAME[$item['name']] ?? '';
        }

        return $list;
    }

    public function modifyTotal(array $params)
    {
        $flg = array_get($params, 'c');
        $result = $this->kvService->getRpcList();
        if ($flg == 'info') {
            return $result;
        }
        $operateId = array_get($params, 'admin_id', 0);
        $operateName = Helper::getAdminName($operateId ?? '');
        $logData = [];
        foreach ($result as $key => $value) {
            if (!isset($params[$key])) {
                continue;
            }
            $weight = array_get($params, $key);
            if ($weight != $value) {
                $this->validation($key, $weight);
                $logData[] = [
                    'content'      => '编辑',
                    'before_json'  => [$key => $value],
                    'after_json'   => [$key => $weight],
                    'type'         => BmsOperateLog::TYPE_OPERATE_LOG,
                    'model'        => 'luckyfruitparams',
                    'model_id'     => GetKvConstant::TAROT_PARAMS_ID[$key],
                    'action'       => BmsOperateLog::ACTION_UPDATE,
                    'operate_id'   => $operateId,
                    'operate_name' => $operateName,
                ];
                $result[$key] = (int) $weight;
            }
        }
        $result['lastUpdateTime'] = time();
        [$res, $msg] = (new PsService())->setLuckyFruitsConfig($result);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
        OperateLog::addOperateBatchLog($logData);
    }

    public function validation(string $key, int $weight): void
    {
        if (in_array($key, ['profit_line', 'profit_money', 'prize_pool_refill_line', 'prize_pool_lower_limit_today', 'global_loss_line']) && (!preg_match("/^[0-9]+$/", $weight))) {
            throw new ApiException(ApiException::MSG_ERROR, '当前修改数值必须为整数');
        }

        if (in_array($key, ['reward_upper_limit_rate', 'system_commission_rate']) && ($weight < 0 || $weight > 100)) {
            throw new ApiException(ApiException::MSG_ERROR, '当前修改数值必须为0-100内数字');
        }
    }
}