<?php


namespace Imee\Service\Domain\Service\Csms\Process\Callback;


use Imee\Comp\Common\Phpnsq\NsqClient;
use Imee\Helper\Constant\NsqConstant;
use Imee\Models\Xs\XsChatroomModify;
use Imee\Service\Domain\Service\Csms\Traits\CsmsTrait;

class XsChatroomIconProcess
{

    use CsmsTrait;

    public function handle($data)
    {
        // 注意这里的ID  是 xs_chatroom_modify表的ID
        $id = $data['pk_value'];
        $status = $data['status'];

        $chatroomModify = XsChatroomModify::findFirst([
            'conditions' => "id = :id:",
            'bind' => [
                'id' => $id
            ]
        ]);

        if($chatroomModify){
            $res = NsqClient::csmsPublish(NsqConstant::TOPIC_XS_ADMIN, [
                'cmd' => 'room.valid',
                'data' => [
                    'id' => $chatroomModify->uid,                               // 单个审核 对应的 房间ID
                    'ac' => $status,                                            // 1通过  2重置为最近通过的头像  3重置为默认图片
                    'pid' => '0',                                               // 待确认
                    'tp' => 'icon',                                             // 待确认  类型 icon
                    're' => ($status == 1) ? '' : $this->_translate($data['uid'], '您的房间头像审核不通过已被重置，原因：含有违规信息'),         // 待确认
                    'op' => $data['admin'],                                     // 审核人员
                    'uids' => $chatroomModify->uid                              // 房间ID
                ]
            ]);
            // 发送失败
            if($res){
                return [
                    'state' => false,
                    'message' => $res
                ];
            }else{
                return [
                    'state' => true,
                    'message' => ''
                ];
            }
        }else{
            return [
                'state' => false,
                'message' => '房间修改信息不存在'
            ];
        }
    }

}