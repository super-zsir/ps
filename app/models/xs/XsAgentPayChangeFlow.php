<?php

namespace Imee\Models\Xs;

class XsAgentPayChangeFlow extends BaseModel
{
    const OP_REASON = array(
        'pay' => '充值',
        'transfer' => '转账',
        'background_give' => '后台下发',
        'extend' => '扩展',
    );
}