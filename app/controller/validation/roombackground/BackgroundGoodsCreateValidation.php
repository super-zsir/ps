<?php

namespace Imee\Controller\Validation\Roombackground;

use Imee\Comp\Common\Validation\Validator;

class BackgroundGoodsCreateValidation extends Validator
{
    protected function rules()
    {
        return [
            'big_area'      => 'required|string',
            'mid'           => 'required|integer',
            'sn'            => 'required|integer',
            'duration'      => 'required|integer|min:1',
            'name'          => 'required|string',
            'state'         => 'required|integer',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'big_area'      => 'Region',
            'mid'           => 'Material ID',
            'sn'            => 'SN',
            'name'          => 'Name',
            'duration'      => 'Duration(Day)',
            'state'         => 'Shelf Status',
        ];
    }

    /**
     * 提示信息
     */
    protected function messages()
    {
        return [
            'integer' => 'invalid param',
            'string' => 'invalid param',
            'duration.min' => 'Duration 只能为正整数',
        ];
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
