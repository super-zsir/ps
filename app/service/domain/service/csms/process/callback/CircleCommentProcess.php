<?php

namespace Imee\Service\Domain\Service\Csms\Process\Callback;

use Imee\Comp\Common\Phpnsq\NsqClient;
use Imee\Helper\Constant\NsqConstant;

class CircleCommentProcess
{
    /**
     *
     * @param array $data
     * @return array
     */
    public function handle(array $data)
    {

        $cmt_id = $data['pk_value'];
        $status = ($data['status'] == 1) ? 'success' : 'failed' ;

        $topic_id = $data['topic_id'] ?? '';
        if(!$topic_id){
            return [
                'state' => false,
                'message' => 'csms'.$data['choice'].'没有动态ID:'.$cmt_id
            ];
        }


        // 通过消息队列发送消息通知用户
        NsqClient::publish(NsqConstant::TOPIC_XS_CIRCLE, array(
            'cmd' => 'comment.verify',
            'data' => array(
                'cmtid' => intval($cmt_id),
                'topic_id' => intval($topic_id),
                'result' => $status,
                'reason' => ''
            ),
        ));


        return [
            'state' => true,
            'message' => ''
        ];

    }
}