<?php

namespace Imee\Controller\Validation\Operate\Pop;

use Imee\Comp\Common\Validation\Validator;

class HomePagePopValidation extends Validator
{
    protected function rules()
    {
        return [
            'icon'       => 'required|string',
            'jump_url'   => 'required|string',
            'bigarea_id' => 'required|integer',
            'order'      => 'required|integer',
            'lv'         => 'required|integer',
            'start_time' => 'required|string',
            'end_time'   => 'required|string',
            'id'         => 'required|integer'
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'id'         => 'ID',
            'icon'       => '弹窗图片',
            'jump_url'   => '跳转链接',
            'bigarea_id' => '大区',
            'order'      => '优先级',
            'lv'         => '用户财富等级',
            'start_time' => '开始时间',
            'end_time'   => '结束时间',
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