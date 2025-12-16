<?php

namespace Imee\Controller\Validation\Operate\Cp;

use Imee\Comp\Common\Validation\Validator;

class PropCardAddValidation extends Validator
{
    protected function rules()
    {
        return [
            'prop_card_config_id' => 'required|integer|min:0',
            'type' => 'required|integer',
            'bigarea_id' => 'required_if:type,1|array',
            'ratio' => 'integer|min:1',
            'relation_type' => 'required_if:type,7,8|integer',
            'buy_use_level' => 'required_if:type,7,8|integer|between:1,7'
//            'config' => 'required_if:type,1|array',
//            'config.*.validity_value' => 'integer|min:-1',
//            'config.*.price' => 'integer|min:0',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'prop_card_config_id' => '物品ID',
            'bigarea_id' => '上架地区',
            'type' => '物品类型',
            'relation_type' => '关系类型',
            'buy_use_level' => '可购买和可使用的关系等级',
//            'config' => '配置',
//            'config.*.validity_value' => '有效期',
//            'config.*.price' => '价格',
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