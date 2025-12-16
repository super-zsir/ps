<?php


namespace Imee\Service\Domain\Service\Csms\Process\Callback;


use Imee\Models\Xs\XsLiveConfig;
use Imee\Service\Domain\Service\Audit\Report\Traits\CommonTrait;
use Imee\Service\Domain\Service\Csms\Traits\CsmsTrait;

class XsLiveConfigProcess
{

    use CsmsTrait;
    use CommonTrait;

    public function handle($data)
    {
        $id = $data['pk_value'];
        $state = $data['status'];

        $is_verify = $state;

        $rec = XsLiveConfig::findFirst([
            'conditions' => 'id = :id:',
            'bind' => [
                'id' => $id
            ]
        ]);
        if(!$rec){
            return [
                'state' => false,
                'message' => 'csms'.$data['choice'].'未找到记录:'.$id
            ];
        }

        $noticeMsg = '';
        $origin_verify_text  = $rec->verify_text;
        $origin_text  = $rec->text;

        $rec->is_verify = $is_verify;

        //拒绝都清空
        if($is_verify == 2 ){
            $rec->text = '';
            $is_pass = 2;
        }

        //通过情况原始的verify_text清空
        if($is_verify == 1 ){
            if($origin_verify_text){
                $rec->text = $origin_verify_text;
            }
            $rec->verify_success_dateline = time();
            $is_pass = 1;
        }

        if($origin_verify_text || $origin_text){
            //二次审核的情况下 $origin_verify_text为空
            $origin_verify_text = $origin_verify_text ? $origin_verify_text : $origin_text  ;
            if($is_pass == 2 ){
                $noticeMsg = '您的粉丝铭牌【%s】未通过审核，请修改后重新提交';
                $noticeMsg = $this->_translate($rec->live_uid, $noticeMsg);
                $noticeMsg = sprintf($noticeMsg, $origin_verify_text);
            }
            if($is_pass == 1 ){
                $noticeMsg = '您的粉丝铭牌【%s】已通过审核';
                $noticeMsg = $this->_translate($rec->live_uid, $noticeMsg);
                $noticeMsg = sprintf($noticeMsg, $origin_verify_text);
            }
        }

        $rec->verify_text = '';
        $rec->save();


        if($noticeMsg){
            self::sendSystemMessage($rec->live_uid, $noticeMsg);
        }


        return [
            'state' => true,
            'message' => ''
        ];
    }

}