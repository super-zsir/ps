<?php

namespace Imee\Service\Domain\Service\Csms\Process\Callback;

use Imee\Comp\Common\Phpnsq\NsqClient;
use Imee\Helper\Constant\NsqConstant;
use Imee\Models\Union\BbuUnion;
use Imee\Service\Domain\Service\Csms\Traits\CsmsTrait;

class BbuUnionProcess
{

    use CsmsTrait;

    public function handle($data)
    {
        $id = $data['pk_value'];
        $state = $data['status'];

        $obj = BbuUnion::findFirst([
            'conditions' => 'id = :id:',
            'bind' => [
                'id' => $id
            ]
        ]);
        if(!$obj){
            return [
                'state' => false,
                'message' => 'csms'.$data['choice'].'未找到:'.$id
            ];
        }

        if($obj->temp_status != 10){
            return [
                'state' => false,
                'message' => '此数据已审核，不可重复审核',
                'retry' => 2
            ];
        }

        if($state == 1){
            if (!empty($obj->temp_logo)) $obj->logo = $obj->temp_logo;
            if (!empty($obj->temp_bg_pic)) $obj->bg_pic = $obj->temp_bg_pic;
            if (!empty($obj->temp_desc)) $obj->desc = $obj->temp_desc;
            if (!empty($obj->temp_name)) $obj->name = $obj->temp_name;
            if (!empty($obj->temp_short_name)) $obj->short_name = $obj->temp_short_name;
        }

        $obj->temp_logo = '';
        $obj->temp_bg_pic = '';
        $obj->temp_desc = '';
        $obj->temp_name = '';
        $obj->temp_short_name = '';
        $obj->temp_status = ($state  == 1) ? 20 : 30;

        if($obj->save()){
            if($state == 2){
                $msg = $this->_translate($obj->create_uid, '您的联盟房间未通过审核');
                NsqClient::publish(NsqConstant::TOPIC_XS_ADMIN, array(
                    'cmd' => 'system.message',
                    'data' => array(
                        'from' => 0,
                        'uid' => $obj->create_uid,
                        'message' => $msg
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