<?php

namespace Imee\Service\Domain\Service\Csms\Process\Callback;

use Imee\Comp\Common\Phpnsq\NsqClient;
use Imee\Helper\Constant\NsqConstant;

class XsUserPhotosProcess
{
    public function handle(array $data)
    {
        return NsqClient::publish(NsqConstant::TOPIC_ADMIN_REVIEW, array(
            'cmd' => 'text.verify',
            'data' => $data,
        ));
    }
}
