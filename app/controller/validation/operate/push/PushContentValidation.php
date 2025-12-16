<?php

namespace Imee\Controller\Validation\Operate\Push;

use Imee\Comp\Common\Validation\Validator;

class PushContentValidation extends Validator
{
    protected function rules()
    {
        return [
            'title'    => 'required|string',
            'content'  => 'required|string',
            'mod'     => 'required|integer',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'title'   => '标题',
            'content' => '内容',
            'mod'     => '关联类型',
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
                'code' => 0,
                'msg' => '',
                'data' => null,
            ],
        ];
    }
}