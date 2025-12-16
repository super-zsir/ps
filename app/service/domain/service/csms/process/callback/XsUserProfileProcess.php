<?php

namespace Imee\Service\Domain\Service\Csms\Process\Callback;

use Imee\Comp\Common\Phpnsq\NsqClient;
use Imee\Helper\Constant\NsqConstant;

class XsUserProfileProcess
{
    /**
     * @param array $data
     * @return bool
     */
    public function handle(array $data)
    {
        return NsqClient::publish(NsqConstant::TOPIC_ADMIN_REVIEW, array(
            'cmd' => 'text.verify',
            'data' => $data,
        ));
    }
}
