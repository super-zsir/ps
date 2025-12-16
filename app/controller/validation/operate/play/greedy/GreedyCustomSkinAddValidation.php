<?php

namespace Imee\Controller\Validation\Operate\Play\Greedy;

use Imee\Comp\Common\Validation\Validator;

class GreedyCustomSkinAddValidation extends Validator
{
    protected function rules()
    {
        return [
            'uid'                  => 'required|integer',
            'name'                 => 'required|string|max:20',
            'title_img'            => 'required|string',
            'theme_img'            => 'required|string',
            'open_prize_theme_img' => 'required|string',
            'bg_img'               => 'required|string',
            'title'                => 'required|string|max:20',
            'effective_time'       => 'required|date',
            'period_time_day'      => 'required|integer|min:1',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'id'                   => 'ID',
            'skin_id'              => 'skin_id',
            'uid'                  => 'UID',
            'name'                 => '定制皮肤名',
            'title_img'            => '标题底板',
            'theme_img'            => '中心主题元素-下注',
            'open_prize_theme_img' => '中心主题元素-开奖',
            'bg_img'               => '背景板',
            'title'                => '定制标题',
            'effective_time'       => '生效时间',
            'period_time_day'      => '有效期',
        ];
    }

    /**
     * 提示信息
     */
    protected function messages()
    {
        return [
            'name.max'  => '定制皮肤名不能超过20个字符',
            'title.max' => '定制标题不能超过20个字符',
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