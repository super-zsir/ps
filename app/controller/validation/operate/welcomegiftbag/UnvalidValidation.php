<?php

namespace Imee\Controller\Validation\Operate\Welcomegiftbag;

use Imee\Comp\Common\Validation\Validator;
use Imee\Models\Xs\XsGiftBag;

class UnvalidValidation extends Validator
{
    protected function rules()
    {
        return [
            'bid' => 'required|integer',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
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