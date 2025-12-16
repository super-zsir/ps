<?php

namespace Imee\Models\Xs;

class XsChatTeamPkDiamondRecord extends BaseModel
{
    protected static $primaryKey = 'id';

    const TEAM_PK_RANK_TYPE_RED_REC = 0; //红方收礼
    const TEAM_PK_RANK_TYPE_BLUE_REC = 1; //蓝方收礼
    const TEAM_PK_RANK_TYPE_RED_SEND = 2; //送礼明细

    public static $endType = [
        0 => '初始值',
        1 => '正常结束',
        2 => '中断结束',
        3 => '异常结束',
    ];
}