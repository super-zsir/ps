<?php

namespace Imee\Service\Domain\Service\Csms\Process\Callback;

use Imee\Comp\Common\Phpnsq\NsqClient;
use Imee\Helper\Constant\NsqConstant;

class TextCsmsProcess
{

    public $config = [
        'xs_user_name' => ['table' => 'xs_user_profile', 'field' => 'name'],
        'xs_user_sign' => ['table' => 'xs_user_profile', 'field' => 'sign'],
        'xs_user_icon' => ['table' => 'xs_user_profile', 'field' => 'tmp_icon'],
        'xs_user_photos' => ['table' => 'xs_user_photos', 'field' => 'path'],
        'xs_chatroom_name' => ['table' => 'xs_chatroom', 'field' => 'name'],
        'xs_chatroom_description' => ['table' => 'xs_chatroom', 'field' => 'description'],
        'xs_chatroom_icon' => ['table' => 'xs_chatroom', 'field' => 'tmp_icon'],
        'xs_fleet_name' => ['table' => 'xs_fleet', 'field' => 'name'],
        'xs_fleet_description' => ['table' => 'xs_fleet', 'field' => 'description'],
        'xs_fleet_icon' => ['table' => 'xs_fleet', 'field' => 'tmp_icon'],
    ];


    public function handle($data = [])
    {


        $pushData = [];
        // 拼接table  field
        $pushData['table'] = $this->config[$data['choice']]['table'];
        $pushData['field'] = $this->config[$data['choice']]['field'];

        $pushData['pk_value'] = $data['pk_value'];

        // 转换origin   value

        $content = $data['value'];
        $types = array_values(array_unique(array_column($content, 'type')));

        // 一种类型的的
        if (count($types) == 1) {

            $origins = array_column($data['origin'], 'value');
            $pushorigin = '';
            if($origins){
                foreach ($origins as $origin){
                    if(is_array($origin)){
                        $pushorigin .= implode('', $origin);
                    }else{
                        $pushorigin .= $origin;
                    }
                }
                $pushData['origin'] = $pushorigin;
            }else{
                $pushData['origin'] = '';
            }


            $values = array_column($content, 'value');
            $pushvalue = '';
            if($values){
                foreach ($values as $text){
                    if(is_array($text)){
                        $pushvalue .= implode('', $text);
                    }else{
                        $pushvalue .= $text;
                    }
                }
                $pushData['value'] = $pushvalue;
            }

        }else{
            return [
                'state' => false,
                'message' => '旧系统审核项只存在一个值，类型错误请查看'
            ];
        }


        $pushData['deleted'] = $data['status'];
        $pushData['review'] = $data['review'];

        // reason
        $pushData['reason'] = $pushData['deleted'] == 1 ? '' : '人工审核';

        // 用户头像的去除域名
        if($data['choice'] == 'xs_user_icon'){
            $pushData['value'] = str_replace(PARTYING_OSS,'', $pushData['value']);
        }


        $msg = NsqClient::csmsPublish(NsqConstant::TOPIC_XS_ADMIN, array(
            'cmd' => 'text.verify',
            'data' => $pushData,
        ));

//        print_r($data);
//        print_r($pushData);

        // 投递失败的
        if($msg){
            return [
                'state' => false,
                'message' => $msg
            ];
        }else{
            return [
                'state' => true,
                'message' => ''
            ];
        }
    }

}