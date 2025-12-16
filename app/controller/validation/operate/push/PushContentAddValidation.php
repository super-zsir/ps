<?php

namespace Imee\Controller\Validation\Operate\Push;

use Imee\Comp\Common\Validation\Validator;

class PushContentAddValidation extends Validator
{
    protected function rules()
    {
        return [
            'cid'       => 'required|integer',
            'title'     => 'required|string',
            'content'   => 'required|string',
            'sex'       => 'required|integer',
            'send_type' => 'required|integer',
            'area'      => 'required|array',
            'page_url'  => 'required|string',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'cid'       => '类型',
            'title'     => '标题',
            'content'   => '内容文本',
            'sex'       => '性别',
            'send_type' => '触发方式',
            'area'      => '大区',
            'page_url'  => '落地页',
        ];
    }

    /**
     * 提示信息
     */
    protected function messages()
    {
        return [];
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