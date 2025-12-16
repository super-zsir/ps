<?php

namespace Imee\Controller\Validation\Operate\Func;

use Imee\Comp\Common\Validation\Validator;

class QuickGiftConfigModifyValidation extends Validator
{
    protected function rules()
    {
        return [
            'gift_id'  => 'required|integer',
            'name'     => 'required|integer',
            'status'   => 'required|integer',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'gift_id'   => '礼物ID',
            'name'      => '大区',
            'status'    => '状态',
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
                'total' => 1,
                'data' => [
                ],
            ],
        ];
    }
}