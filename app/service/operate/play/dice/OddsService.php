<?php

namespace Imee\Service\Operate\Play\Dice;

use Imee\Models\Xs\XsGlobalConfig;
use Imee\Models\Xsst\BmsOperateHistory;
use Imee\Models\Xsst\BmsOperateLog;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;

class OddsService
{
    public function getList(): array
    {
        $res = XsGlobalConfig::getDiceConfigOdds();
        $logs = BmsOperateLog::getFirstLogList('diceplayodds', array_column($res, 'id'));
        foreach ($res as &$v) {
            $v['admin_name'] = $logs[$v['id']]['operate_name'] ?? '-';
            $v['dateline'] = isset($logs[$v['id']]['created_time']) ? Helper::now($logs[$v['id']]['created_time']) : '';
        }
        return $res;
    }

    public function modify(int $id, int $rate): array
    {
        $config = XsGlobalConfig::findByType(XsGlobalConfig::DICE_PLAY);
        if (!$config) {
            return [false, '数据不存在'];
        }
        $beforeJson = [];
        foreach ($config['sic_bo_config'] as &$v) {
            if ($v['SIC_BO_ID'] == $id) {
                $beforeJson = [
                    'SIC_BO_ID' => $id,
                    'hit_rate' => $v['hit_rate']
                ];
                $v['hit_rate'] = $rate;
            }
        }
        [$res, $msg] = (new PsService())->setSicBoConfig($config);
        if (!$res) {
            return [false, $msg];
        }
        return [true, ['before_json' => $beforeJson, 'after_json' => [
            'SIC_BO_ID' => $id,
            'hit_rate' => $rate
        ]]];
    }
}