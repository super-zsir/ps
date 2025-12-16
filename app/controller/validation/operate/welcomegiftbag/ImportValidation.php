<?php

namespace Imee\Controller\Validation\Operate\Welcomegiftbag;

use Imee\Comp\Common\Validation\Validator;
use Imee\Models\Xs\XsGiftBag;

class ImportValidation extends Validator
{
    protected function rules()
    {
        return [
            'data' => 'required|array',
            
            'data.*.gb_id' => 'required|integer',
            'data.*.uid' => 'required|integer',
            'data.*.num' => 'required|integer|min:1',
            'data.*.valid_day' => 'required|integer|min:1',
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