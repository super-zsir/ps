<?php

namespace Imee\Models\Nsq;

use Imee\Comp\Common\Phpnsq\NsqClient;
use Imee\Helper\Constant\NsqConstant;

class CircleNsq
{
    /**
     * 动态标签
     */
    public static function publishRsCircleTagEs($cmd, $id): bool
    {
        return NsqClient::publish(NsqConstant::TOPIC_RS_CIRCLE_TAG, [
            'cmd' => $cmd,
            'data' => ['id' => $id],
        ]);
    }
}