<?php


namespace Imee\Service\Domain\Service\Csms\Process\Callback;


use Imee\Comp\Common\Phpnsq\NsqClient;
use Imee\Helper\Constant\NsqConstant;
use Imee\Service\Domain\Service\Csms\Traits\CsmswarningTrait;

class IconIdGsProcess
{
    use CsmswarningTrait;

    /**
     * 印尼gs头像处理
     * @param $data
     * @return array
     */
    public function handle($data)
    {

        // 先往老系统发
        $table = 'xs_user_profile';
        $field = 'tmp_icon';
        $pk_value = $data['pk_value'];
        $origin = $data['origin'][0]['value'][0] ?? '';
        $value = $data['value'][0]['value'][0] ?? '';
        $deleted = $data['status'];
        $review = $data['review'];

        $nsqData = [
            'table' => $table,
            'field' => $field,
            'pk_value' => $pk_value,
            'origin' => str_replace(CDN_IMG_DOMAIN, '', $origin),
            'value' => str_replace(CDN_IMG_DOMAIN, '', $value),
            'deleted' => $deleted,
            'review' =>$review,
            'reason' => '人工审核'
        ];

        $msg = NsqClient::csmsPublish(NsqConstant::TOPIC_XS_ADMIN, [
            'cmd' => 'text.verify',
            'data' => $nsqData
        ]);

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