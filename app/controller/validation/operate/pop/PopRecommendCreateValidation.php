<?php

namespace Imee\Controller\Validation\Operate\Pop;

use Imee\Comp\Common\Validation\Validator;

class PopRecommendCreateValidation extends Validator
{
    protected function rules()
    {
        return [
            'bigarea_id'     => 'required|integer',
            'start_time'     => 'required|string',
            'end_time'       => 'required|string|after:start_time',
            'recommend_type' => 'required|integer',
            'background_img' => 'required_if:recommend_type,2|string',
            'jump_url'       => 'required_if:recommend_type,2|string',
            'recommend_rule' => 'required_if:recommend_type,1|array'
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'id'             => 'ID',
            'bigarea_id'     => '大区',
            'start_time'     => '开始时间',
            'end_time'       => '结束时间',
            'recommend_type' => '推荐类型',
            'background_img' => '背景图片',
            'jump_url'       => '跳转链接',
            'recommend_rule' => '推荐规则',
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