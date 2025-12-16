<?php

namespace Imee\Service\Domain\Service\Csms\Process\Callback;

use Imee\Comp\Common\Phpnsq\NsqClient;
use Imee\Helper\Constant\NsqConstant;
use Imee\Models\Xs\XsRushUserFriendCard;
use Imee\Service\Domain\Service\Csms\Traits\CsmsTrait;

class FriendCardProcess
{
    use CsmsTrait;

    public function handle(array $data)
    {
        $pkValue = $data['pk_value'];
        $uid = $pkValue;
        $state = $data['status'];

        $res = XsRushUserFriendCard::findFirst([
            "uid=:uid: ",
            "bind" => array("uid" => $pkValue)
        ]);
        if(empty($res)){
            return [
                'state' => false,
                'message' => 'csms'.$data['choice'].'未找到uid:'.$uid
            ];
        }

        $hasUpdate = false;
        $hasNotice = false;

        if ($res->checked != $state) {
            $res->checked = $state;
            // 审核不通过的情况
            if ($state == 2) {
                $res->audio = '';
                $hasNotice = true;
            }
            $hasUpdate = true;
        }

        if ($hasUpdate) {
            $res->desc = empty($res->desc) ? '' : $res->desc;
            $res->save();
        }

        // 只有拒绝的发消息
        if ($hasUpdate && $hasNotice) {
            $notice = $this->_translate($uid, '您的声音未通过审核，可在编辑资料中重新录音，来获取更多展示自己的机会');
            NsqClient::publish(NsqConstant::TOPIC_XS_CMD, array(
                'cmd' => 'live.message',
                'data' => array(
                    'from' => SystemNoticeVerify,
                    'to' => $uid,
                    'message' => $notice,
                    'extra' => null,
                )
            ));
        }
        return [
            'state' => true,
            'message' => ''
        ];

    }
}
