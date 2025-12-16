<?php

namespace Imee\Controller\Validation\Operate\Pretty\Commodity;

use Imee\Comp\Common\Validation\Validator;
use Imee\Models\Xs\XsCommodityPrettyInfo;

class ShelfValidation extends Validator
{
    protected function rules()
    {
        return [
            'id' => 'required|array',
            'id.*' => 'required|integer|min:0',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'id' => 'id',
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
