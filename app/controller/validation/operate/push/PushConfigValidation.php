<?php


namespace Imee\Controller\Validation\Operate\Push;


use Imee\Comp\Common\Validation\Validator;

class PushConfigValidation extends Validator
{
    protected function rules()
    {
        return [
            'title'       => 'required|string|max:100',
            'subtitle'    => 'required|string|max:150',
            'package_id'  => 'required|integer',
            'push_method' => 'required|integer',
            'push_uid'    => 'string',
            'page_link'   => 'required|string',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'title'       => '标题',
            'subtitle'    => '副标题',
            'package_id'  => '定向包',
            'push_method' => '重复',
            'push_uid'    => '上传UID',
            'page_link'   => '链接',
        ];
    }

    /**
     * 提示信息
     */
    protected function messages()
    {
        return [
            'send_time.after' => "发送时间不能小于当前时间"
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