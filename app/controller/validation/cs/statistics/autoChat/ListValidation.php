<?php

namespace Imee\Controller\Validation\Cs\Statistics\AutoChat;

use Imee\Comp\Common\Validation\Validator;

class ListValidation extends Validator
{
    protected function rules()
    {
        return [
            'start' => 'date',
            'end' => 'date',
            'tag' => 'string',
            'statistical_type' => 'integer|required',
            'language' => 'string',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'start' => '开始时间',
            'end' => '结束时间',
            'tag' => '标签',
            'statistical_type' => '统计类型',
            'language' => '大区',
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
