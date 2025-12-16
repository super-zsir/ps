<?php

namespace Imee\Controller\Validation\Audit\Dirtytrigger;

use Imee\Comp\Common\Validation\Validator;

class DirtyTriggerModifyValidation extends Validator
{
    protected function rules()
    {
        return [
            'ids' => 'required|array',
            'state' => 'required|integer'
        ];
    }


    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'id' => '记录',
            'state' => '状态'
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
