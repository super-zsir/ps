<?php

namespace Imee\Controller\Validation\Operate\Nocodetest;

use Imee\Comp\Common\Validation\Validator;

class DeleteBatchValidation extends Validator
{
    protected function rules()
    {
        return [
            'uids' => 'required|array',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'uids' => 'UIDS',
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
                'data'    => null,
            ],
        ];
    }

}