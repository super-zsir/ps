<?php

namespace Imee\Controller\Validation\Operate\Background\Custombackground;

use Imee\Comp\Common\Validation\Validator;

class CustomBackgroundValidation extends Validator
{
    protected function rules(): array
    {
        return [
            'image' => 'required|string',
            'cover' => 'required|string',
        ];
    }

    /**
     * 属性
     */
    protected function attributes(): array
    {
        return [
            'image'           =>  'Image',
            'cover'           =>  'Cover',
        ];
    }

    /**
     * 提示信息
     */
    protected function messages(): array
    {
        return [];
    }

    /**
     * 返回数据结构
     */
    protected function response(): array
    {
        return [
            'result' => [
                'success' => true,
                'code'    => 0,
                'msg'     => '',
                'data'    => true,
            ],
        ];
    }
}