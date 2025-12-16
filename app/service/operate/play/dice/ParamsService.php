<?php

namespace Imee\Service\Operate\Play\Dice;

use Imee\Models\Xs\XsGlobalConfig;
use Imee\Models\Xsst\BmsOperateHistory;
use Imee\Models\Xsst\BmsOperateLog;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;

class ParamsService
{
    public function getList(): array
    {
        $res = XsGlobalConfig::getParamsConfigByType(XsGlobalConfig::DICE_PLAY);
        $logs = BmsOperateLog::getFirstLogList('diceplayparams', array_column($res, 'id'));
        foreach ($res as &$v) {
            $v['admin_name'] = $logs[$v['id']]['operate_name'] ?? '-';
            $v['dateline'] = isset($logs[$v['id']]['created_time']) ? Helper::now($logs[$v['id']]['created_time']) : '';
        }
        return $res;
    }

    public function modify(int $id, string $keys, int $weight): array
    {
        [$valRes, $msg] = $this->validation($keys, $weight);
        if (!$valRes) {
            return [false, $msg];
        }
        $config = XsGlobalConfig::findByType(XsGlobalConfig::DICE_PLAY);
        if (!$config) {
            return [false, '数据不存在'];
        }
        if (!isset($config[$keys])) {
            return [false, '当前配置不存在'];
        }

        $beforeJson = [
            'id' => $id,
            $keys => $config[$keys]
        ];

        $config[$keys] = $weight;

        [$res, $msg] = (new PsService())->setSicBoConfig($config);
        if (!$res) {
            return [false, $msg];
        }
        return [true, ['before_json' => $beforeJson, 'after_json' => [
            'id' => $id,
            $keys => $config[$keys]
        ]]];
    }

    public function validation(string $keys, int $weight)
    {
        $config = XsGlobalConfig::findByType(XsGlobalConfig::DICE_PLAY);
        if ($keys == 'profit_line') {
            if ($weight < 2000000 || $weight < ($config['profit_money'] * 2)) {
                return [false, '利润分割线必须大于200万，不能小于利润分割金额的两倍'];
            } else {
                return [true, ''];
            }
        } else if ($keys == 'profit_money') {
            if ($weight > ($config['profit_line'] / 2)) {
                return [false, '利润分割金额不可超过利润分割线的50%'];
            } else {
                return [true, ''];
            }
        } else if ($keys == 'reward_upper_limit_rate' || $keys == 'gold_finger_rate') {
            if ($weight > 10000) {
                return [false, '设置数值不可大于10000'];
            } else {
                return [true, ''];
            }
        } else {
            return [true, ''];
        }
    }
}