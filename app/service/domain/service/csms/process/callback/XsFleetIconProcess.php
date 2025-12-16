<?php

namespace Imee\Service\Domain\Service\Csms\Process\Callback;

use Imee\Comp\Common\Phpnsq\NsqClient;
use Imee\Helper\Constant\CsmsConstant;
use Imee\Helper\Constant\NsqConstant;
use Imee\Models\Xs\XsFleet;
use Imee\Models\Xs\XsFleetModify;
use Imee\Models\Xs\XsFleetModifylog;
use Imee\Service\Domain\Service\Csms\Traits\CsmsTrait;

class XsFleetIconProcess
{
    use CsmsTrait;

    public function handle($data)
    {
        $gid = $data['pk_value'];
        $status = $data['status'];

        // 获取值
        $values = array_column($data['value'], 'value');
        $pushvalue = '';
        if($values){
            foreach ($values as $text){
                if(is_array($text)){
                    $pushvalue .= implode('', $text);
                }else{
                    $pushvalue .= $text;
                }
            }
        }
        if(!$pushvalue){
            return [
                'state' => false,
                'message' => '外显value为空异常，请核对'
            ];
        }

        // 查看信息
        $fleet = XsFleet::findFirst([
            'conditions' => 'gid = :gid:',
            'bind' => [
                'gid' => $gid
            ]
        ]);
        if(!$fleet){
            return [
                'state' => false,
                'message' => '家族信息不存在，请核对'
            ];
        }

        $noticeUid = $fleet->uid;

        // 如果原先有更新记录
        if($fleet->tmp_icon){

            $fleet->tmp_icon = '';
            if($status == CsmsConstant::CSMS_STATE_PASS){
                $fleet->icon = $pushvalue;
                $noticeReason = "恭喜您通过了家族封面审核！";
            }else{
                $noticeReason = '您提交的家族封面未通过审核。请勿上传含有色情、暴力、涉政、反动、广告等违法违规内容的封面。';
            }
            $fleet->save();

            // 增加审核记录
            XsFleetModify::updateRows($gid, 'icon', [
                'val' => $fleet->tmp_icon,
                'state' => $status,
                'update_time' => time(),
                'admin_id' => $data['admin'],
                'modify_time' => time(),
                'reason' => ($status == 1) ? '' : $noticeReason
            ]);

            // 发送消息
            $noticeType = 'icon';
            $hasLast = XsFleetModifylog::getValueByKey($gid, $noticeType);
            if($hasLast){
                $noticeUid = $hasLast->uid;
            }
            $noticeReason = $this->_translate($noticeUid, $noticeReason);
            if($noticeUid >1 && $noticeReason){
                NsqClient::publish(NsqConstant::TOPIC_XS_ADMIN, array(
                    'cmd' => 'system.message',
                    'data' => array(
                        'from' => 0,
                        'uid' => $noticeUid,
                        'message' => $noticeReason
                    )
                ));
            }
        }


        return [
            'state' => true,
            'message' => ''
        ];
    }

}