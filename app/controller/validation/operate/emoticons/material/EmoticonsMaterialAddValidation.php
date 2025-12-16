<?php

namespace Imee\Controller\Validation\Operate\Emoticons\Material;

use Imee\Comp\Common\Validation\Validator;

class EmoticonsMaterialAddValidation extends Validator
{
    protected function rules()
    {
        return [
            'meta_name'             => 'required|string|max:50',
            'meta_name_en'          => 'string|max:50',
            'is_odds'               => 'required|integer',
            'emoticons'             => 'required|array',
            //'emoticons.*.icon_show' => 'required|string',
            'emoticons.*.name_cn'   => 'required|string',
            'emoticons.*.name_en'   => 'required|string',
            'emoticons.*.extra'     => 'required|array'
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'meta_name'             => '表情包名称',
            'meta_name_en'          => '表情包名称英语',
            'is_odds'               => '是否预期表情',
            'emoticons'             => '表情配置',
            'emoticons.*.icon_show' => '面板展示图',
            'emoticons.*.name_cn'   => '名称-中文',
            'emoticons.*.name_en'   => '名称-英文',
            'emoticons.*.extra'     => '表情',
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