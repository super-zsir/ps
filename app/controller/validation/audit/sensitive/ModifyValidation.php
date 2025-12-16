<?php

namespace Imee\Controller\Validation\Audit\Sensitive;

class ModifyValidation extends AddValidation
{
    protected function rules()
    {
        $rules = parent::rules();
        return array_merge($rules, ['id' => 'required|integer']);
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        $attributes = parent::attributes();
        return array_merge($attributes, ['id' => 'ID']);
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
