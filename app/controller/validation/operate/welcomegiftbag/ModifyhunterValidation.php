<?php

namespace Imee\Controller\Validation\Operate\Welcomegiftbag;

use Imee\Comp\Common\Validation\Validator;
use Imee\Models\Xs\XsGiftBag;

class ModifyhunterValidation extends Validator
{
    protected function rules()
    {
        return [
            'num' => 'required|integer|min:1',
            'id' => 'required|integer',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'num' => '礼包总数量',
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