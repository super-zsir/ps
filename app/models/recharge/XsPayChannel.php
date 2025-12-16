<?php

namespace Imee\Models\Recharge;

class XsPayChannel extends BaseModel
{
    const VAT_TYPE_MAP = [
        0 => '无税',
        1 => '用户承担',
        2 => '平台承担'
    ];

    const RISK_LEVEL_MAP = [3 => '高', 2 => '中', 1 => '低'];

    public static function isHighRiskChannel($payProductId = ''): bool
    {
        if (empty($payProductId)) {
            return false;
        }
        $config = XsIapConfig::findFirst($payProductId);
        if (empty($config) || empty($config->channel)) {
            return false;
        }
        return (bool)(self::useMaster()->findFirst(['name=:name: and risk_level=3', 'bind' => ['name' => $config->channel]]));
    }
}