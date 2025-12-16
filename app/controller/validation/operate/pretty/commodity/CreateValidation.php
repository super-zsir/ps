<?php

namespace Imee\Controller\Validation\Operate\Pretty\Commodity;

use Imee\Comp\Common\Validation\Validator;
use Imee\Models\Xs\XsCommodityPrettyInfo;

class CreateValidation extends Validator
{
    protected function rules()
    {
        return [
            'pretty_uid' => 'required',
            'weight' => 'required|integer|min:0',
            'support_area' => 'required|array',
            'price_info' => 'required|array|min:1|max:3',
            'price_info.*.effective_day' => 'required|integer|min:0',
            'price_info.*.price' => 'required|integer|min:0',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'pretty_uid' => '靓号ID',
            'support_area' => '大区',
            'on_sale_status' => '上架状态',
            'weight' => '权重',
            'price_info' => '价格信息',
            'price_info.*.effective_day' => '有效期',
            'price_info.*.price' => '价格',
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
