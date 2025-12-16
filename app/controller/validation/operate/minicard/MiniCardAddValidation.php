<?php

namespace Imee\Controller\Validation\Operate\Minicard;

use Imee\Comp\Common\Validation\Validator;

class MiniCardAddValidation extends Validator
{
    protected function rules()
    {
        return [
            'name'           => 'required|array',
            'name.zh_cn'     => 'required|string',
            'name.en'        => 'required|string',
            'icon'           => 'required|string',
            'type'           => 'required|integer',
            'minicard_style' => 'required_if:type,1|string',
            'homepage_dress_style' => 'required_if:type,2|string',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'name'           => '资源名称',
            'name.zh_cn'     => '简体中文(zh_cn)',
            'name.en'        => '英文(en)',
            'icon'           => '资源封面',
            'minicard_style' => '资源样式',
            'homepage_dress_style' => '资源样式',
            'type'           => '资源类型',
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
                'total'   => 1,
                'data'    => [
                ],
            ],
        ];
    }
}