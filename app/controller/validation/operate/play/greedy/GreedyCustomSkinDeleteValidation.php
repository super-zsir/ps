<?php

namespace Imee\Controller\Validation\Operate\Play\Greedy;

use Imee\Comp\Common\Validation\Validator;

class GreedyCustomSkinDeleteValidation extends Validator
{
    protected function rules()
    {
        return [
            'id'      => 'required|integer',
            'skin_id' => 'required|string',
            'uid'     => 'required|integer',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'id'      => 'ID',
            'skin_id' => 'skin_id',
            'uid'     => 'UID',
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