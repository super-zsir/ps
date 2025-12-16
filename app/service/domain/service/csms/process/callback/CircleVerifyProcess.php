<?php

namespace Imee\Service\Domain\Service\Csms\Process\Callback;

use Imee\Comp\Common\Phpnsq\NsqClient;
use Imee\Helper\Constant\NsqConstant;

class CircleVerifyProcess
{
    public function handle($data)
    {
        $uid = $data['uid'];
        $topic_id = $data['pk_value'];
        $status = $data['status'] == 1 ? 'success' : 'failed';

        NsqClient::publish(NsqConstant::TOPIC_XS_CIRCLE, array(
            'cmd' => 'topic.verify',
            'data' => array(
                'uid' => intval($uid),
                'topic_id' => intval($topic_id),
                'result' => $status,
                'reason' => ''
            )
        ));

        return [
            'state' => true,
            'message' => ''
        ];
    }

}