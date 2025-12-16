<?php

namespace Imee\Controller\Validation\Operate\Emoticons\Reward;

use Imee\Comp\Common\Validation\Validator;

class ReduceValidation extends Validator
{
    protected function rules()
    {
        return [
            'emoticons_id' => 'required|integer|min:1',
            'uid'          => 'required|integer|min:1',
            'reduce_time'  => 'required|integer|min:1',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'emoticons_id' => '配置ID',
            'reduce_time'  => '回收天数',
            'uid'          => 'UID',
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