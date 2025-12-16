<?php

namespace Imee\Controller\Validation\Operate\Pretty\Style;

use Imee\Comp\Common\Validation\Validator;
use Imee\Models\Xs\XsCustomizePrettyStyle;

class CreateValidation extends Validator
{
    protected function rules()
    {
        return [
            'name'                => 'required',
            'style_type'          => 'required|integer',
            'short_limit'         => 'required|integer|min:1',
            'long_limit'          => 'required|integer|min:1',
            'repeat_limit'        => 'required|integer|min:0',
            'correct_example_1'   => 'required',
            'correct_example_2'   => 'required',
            'incorrect_example_1' => 'required',
            'incorrect_example_2' => 'required',
            'remark'              => 'required',
            'ar_short_limit'      => 'required_if:style_type,' . XsCustomizePrettyStyle::STYLE_TYPE_ENGLISH_NUMBER_ARABIC . '|integer',
            'ar_long_limit'       => 'required_if:style_type,' . XsCustomizePrettyStyle::STYLE_TYPE_ENGLISH_NUMBER_ARABIC . '|integer',
            'tr_short_limit'      => 'required_if:style_type,' . XsCustomizePrettyStyle::STYLE_TYPE_ENGLISH_NUMBER_TR . '|integer',
            'tr_long_limit'       => 'required_if:style_type,' . XsCustomizePrettyStyle::STYLE_TYPE_ENGLISH_NUMBER_TR . '|integer',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'name'                => '类型名称',
            'style_type'          => '靓号支持格式',
            'short_limit'         => '靓号最短字符数',
            'long_limit'          => '靓号最长字符数',
            'repeat_limit'        => '同一字符最多出现次数',
            'correct_example_1'   => '正确实例1',
            'correct_example_2'   => '正确实例2',
            'incorrect_example_1' => '错误实例1',
            'incorrect_example_2' => '错误实例2',
            'remark'              => '备注',
            'ar_short_limit'      => '阿语靓号最短字符',
            'ar_long_limit'       => '阿语靓号最长字符',
            'tr_short_limit'      => '土语靓号最短字符',
            'tr_long_limit'       => '土语靓号最长字符',
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
