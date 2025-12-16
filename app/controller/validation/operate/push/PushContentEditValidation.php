<?php

namespace Imee\Controller\Validation\Operate\Push;

use Imee\Comp\Common\Validation\Validator;

class PushContentEditValidation extends Validator
{
    protected function rules()
    {
        return [
            'id'       => 'required|integer',
            'title'    => 'required|string',
            'content'  => 'required|string',
            'status'   => 'required|integer',
            'page_url' => 'required|string',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'id'       => 'ID',
            'title'    => '标题',
            'content'  => '内容文本',
            'status'   => '状态',
            'page_url' => '落地页',
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