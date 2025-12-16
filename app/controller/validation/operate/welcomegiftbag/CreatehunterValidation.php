<?php

namespace Imee\Controller\Validation\Operate\Welcomegiftbag;

use Imee\Comp\Common\Validation\Validator;
use Imee\Models\Xs\XsGiftBag;

class CreatehunterValidation extends Validator
{
    protected function rules()
    {
        return [
            'gb_id' => 'required|integer',
            'uid' => 'required|string',
            'num' => 'required|integer|min:1',
            'valid_day' => 'required|integer|min:1',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'gb_id' => '礼包ID',
            'num' => '礼包总数量',
            'valid_day' => '有效天数',
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