<?php

namespace Imee\Controller\Validation\Roombackground;

use Imee\Comp\Common\Validation\Validator;

class BackgroundGoodsModifyValidation extends Validator
{
    protected function rules()
    {
        return [
            'bg_id'         => 'required|integer',
            'sn'            => 'required|integer',
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
            'bg_id'         => 'ID',
            'sn'            => 'SN',
            'name'          => 'Name',
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
