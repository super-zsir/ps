<?php

namespace Imee\Controller\Validation\Roombackground;

use Imee\Comp\Common\Validation\Validator;

class BackgroundCreateValidation extends Validator
{
    protected function rules()
    {
        return [
            'name'    => 'required|string',
            'image'   => 'required|string',
            'cover'   => 'required|string',
            'is_free' => 'required|string',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'name'    => 'Name',
            'image'   => 'Image',
            'cover'   => 'Cover',
            'free'    => 'Free',
        ];
    }

    /**
     * 提示信息
     */
    protected function messages()
    {
        return [
            'integer' => 'invalid param',
            'string' => 'invalid param',
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
                'code' => 0,
                'msg' => '',
                'data' => null,
            ],
        ];
    }
}
