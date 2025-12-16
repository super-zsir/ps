<?php


namespace Imee\Controller\Validation\Operate\Push;


use Imee\Comp\Common\Validation\Validator;

class PushManagementValidation extends Validator
{
    protected function rules()
    {
        return [
            'from_id'                 => 'required|integer',
            'push_range'              => 'required|integer',
            'msg_type'                => 'required|string',
            'title'                   => 'string',
            'msg_content'             => 'required_if:msg_type,text,link|string',
            'picture'                 => 'required_if:msg_type,picture|string',
            'link'                    => 'required_if:msg_type,link|string|url',
            'uid_list'                => 'required_if:push_range,0|array',
            'registration_time_sdate' => 'date',
            'registration_time_edate' => 'date',
            'big_area_id'             => 'required_if:push_range,4|integer',
            'country'                 => 'array',
            'role'                    => 'required_if:push_range,4|array',
            'online_time'             => 'required_if:push_range,4|integer',
            'note'                    => 'string',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'from_id'                 => '推送通道',
            'push_range'              => '推送范围',
            'msg_type'                => '消息类型',
            'title'                   => '标题',
            'msg_content'             => '文本',
            'picture'                 => '图片',
            'link'                    => '链接',
            'uid_list'                => '推送名单',
            'registration_time_sdate' => '注册时间',
            'registration_time_edate' => '注册时间',
            'big_area_id'             => '大区',
            'country'                 => '国家',
            'role'                    => '选中人群',
            'online_time'             => '近期活跃',
            'note'                    => '备注',
        ];
    }

    /**
     * 提示信息
     */
    protected function messages()
    {
        return [
            'msg_content.required_if' => "请填写文本",
            'picture.required_if'     => "请上传图片链接",
            'link.required_if'        => "请填写链接",
            'link.url'                => "请填写正确的链接",
        ];
    }

    /**
     * 返回数据结构
     */
    protected function response()
    {
        return [
            'result' => [
                'success' => true,
                'code'    => 0,
                'msg'     => '',
                'data'    => [],
            ],
        ];
    }
}