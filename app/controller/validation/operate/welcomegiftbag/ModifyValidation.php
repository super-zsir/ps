<?php

namespace Imee\Controller\Validation\Operate\Welcomegiftbag;

use Imee\Comp\Common\Validation\Validator;
use Imee\Models\Xs\XsGiftBag;

class ModifyValidation extends Validator
{
    protected function rules()
    {
        return [
            'bid' => 'required|integer',
            'cn' => 'required|max:20',
            'en' => 'required',
            'ar' => '',
            'ms' => '',
            'id' => '',
            'vi' => '',
            'hi' => '',
            'bn' => '',
            'ur' => '',
            'tl' => '',
            'remark' => 'string|max:20',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'reward' => '物品',
            'remark' => '备注',
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