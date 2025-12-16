<?php

namespace Imee\Controller\Validation\Operate;

use Imee\Comp\Common\Validation\Validator;

class OpenScreenValidation extends Validator
{
    protected function rules()
    {
        return [
            'name'           => 'required|string',
            'img'            => 'required|string',
            'visible_crowd'  => 'required|integer',
            'jump_addr_type' => 'required|integer',
            'jump_addr'      => 'required|string',
            'weight'         => 'required|integer|min:1',
            'start_time'     => 'required|date',
            'end_time'       => 'required|date',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'name'           => '开屏页名称',
            'img'            => '开屏图',
            'visible_crowd'  => '可见人群',
            'jump_addr_type' => '跳转地址',
            'jump_addr'      => '跳转内容',
            'weight'         => '权重',
            'start_time'     => '展示开始时间',
            'end_time'       => '展示结束时间',
        ];
    }

    /**
     * 提示信息
     */
    protected function messages()
    {
        return [
            'weight.min' => '权重为正整数'
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
                'code'    => 0,
                'msg'     => '',
                'data'    => null,
            ],
        ];
    }
}