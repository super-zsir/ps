<?php

namespace Imee\Service\Domain\Service\Csms\Process\Callback;

use Imee\Comp\Common\Phpnsq\NsqClient;
use Imee\Helper\Constant\NsqConstant;

class RoomCustomBackgroundProcess
{
    public function handle(array $data)
    {
        return NsqClient::publish(NsqConstant::TOPIC_XS_ADMIN, array(
            'cmd' => 'text.verify',
            'data' => $data,
        ));
    }
}
