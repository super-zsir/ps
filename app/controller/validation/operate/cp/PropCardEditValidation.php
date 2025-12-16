<?php

namespace Imee\Controller\Validation\Operate\Cp;

use Imee\Comp\Common\Validation\Validator;

class PropCardEditValidation extends Validator
{
    protected function rules()
    {
        return [
            'id' => 'required|integer|min:0',
            'prop_card_config_id' => 'integer|min:0',
            'validity_value' => 'integer|min:-1',
            'price' => 'integer|min:0',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'id' => 'ID',
            'prop_card_config_id' => '物品ID',
            'validity_value' => '有效期',
            'price' => '价格',
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