<?php

namespace Imee\Models\Xs;

class XsActivityScoreWallet extends BaseModel
{
    protected static $primaryKey = 'id';

    const SCORE_TYPE_WHEEL_LOTTERY = 11; // 转盘积分
    const SCORE_TYPE_EXCHANGE = 13; // 兑换积分

}