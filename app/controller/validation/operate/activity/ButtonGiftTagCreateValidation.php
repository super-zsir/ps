<?php

namespace Imee\Controller\Validation\Operate\Activity;

use Imee\Comp\Common\Validation\Validator;

/**
 * 礼物榜ButtonTag管理
 */
class ButtonGiftTagCreateValidation extends Validator
{
    protected function rules(): array
    {
        return [
            'act_id'         => 'required|integer',
            'rank_object'    => 'required|integer',
            'button_content' => 'required|string',
        ];
    }

    /**
     * 属性
     */
    protected function attributes(): array
    {
        return [
            'act_id'         => '活动ID',
            'rank_object'    => '面向对象',
            'button_content' => '礼物榜按钮文案',
        ];
    }

    /**
     * 提示信息
     */
    protected function messages(): array
    {
        return [];
    }

    /**
     * 返回数据结构
     */
    protected function response(): array
    {
        return [
            'result' => [
                'success' => true,
                'code'    => 0,
                'msg'     => '',
                'data'    => true,
            ],
        ];
    }
}
