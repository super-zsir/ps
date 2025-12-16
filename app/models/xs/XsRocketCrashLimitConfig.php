<?php

namespace Imee\Models\Xs;

class XsRocketCrashLimitConfig extends BaseModel
{
    const CRASH_TOTAL = 1;      // 子游戏大盘调控配置
    const CRASH_VALUE = 2;      // 贡献值调控配置
    const CRASH_OVERTIME = 3;   // 下车超时调控配置
}