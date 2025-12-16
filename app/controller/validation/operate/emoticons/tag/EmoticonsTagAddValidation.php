<?php

namespace Imee\Controller\Validation\Operate\Emoticons\Tag;

use Imee\Comp\Common\Validation\Validator;

class EmoticonsTagAddValidation extends Validator
{
    protected function rules()
    {
        return [
            'name' => 'required|string|max:64',
            'icon' => 'required|string',
            'pay'  => 'required|integer',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'id'   => 'TagID',
            'name' => 'Tag名称',
            'icon' => 'Tag图片',
            'pay'  => '标签类型',
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