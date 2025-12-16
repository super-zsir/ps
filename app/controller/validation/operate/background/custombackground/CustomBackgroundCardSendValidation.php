<?php

namespace Imee\Controller\Validation\Operate\Background\Custombackground;

use Imee\Comp\Common\Validation\Validator;

class CustomBackgroundCardSendValidation extends Validator
{
    protected function rules(): array
    {
        return [
            'uid'           =>  'required|string',
            'num'           =>  'required|integer',
            'valid_term'    =>  'required|integer|min:1',
            'reason'        =>  'required|string',
        ];
    }

    /**
     * 属性
     */
    protected function attributes(): array
    {
        return [
            'uid'           =>  'UID',
            'num'           =>  '数量',
            'valid_term'    =>  '单张有效期',
            'reason'        =>  '发放理由',
        ];
    }

    /**
     * 提示信息
     */
    protected function messages(): array
    {
        return [
            'valid_term.min' => '单张有效期为非0的自然数'
        ];
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