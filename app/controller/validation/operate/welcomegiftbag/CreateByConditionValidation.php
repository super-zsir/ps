<?php

namespace Imee\Controller\Validation\Operate\Welcomegiftbag;

use Imee\Comp\Common\Validation\Validator;
use Imee\Models\Xs\XsGiftBag;

class CreateByConditionValidation extends Validator
{
    protected function rules()
    {
        return [
            'gb_id'      => 'required|integer',
            'bigarea_id' => 'required|integer|min:1',
            'num'        => 'required|integer|min:1',
            'valid_day'  => 'required|integer|min:1',
            'type'       => 'required|integer|in:1,2'
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'gb_id'      => '礼包ID',
            'bigarea_id' => '大区',
            'num'        => '礼包数量',
            'valid_day'  => '有效天数',
            'type'       => '发放类型'
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