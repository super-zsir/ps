<?php

namespace Imee\Controller\Validation\Operate\Activity;

use Imee\Comp\Common\Validation\Validator;

/**
 * 礼物榜ButtonList管理
 */
class ButtonGiftListCreateValidation extends Validator
{
    protected function rules(): array
    {
        return [
            'button_tag_id'  => 'required|integer',
            'level'          => 'required|integer',
            'button_content' => 'required|string',
            'start_time'     => 'required|string',
            'end_time'       => 'required|string',
        ];
    }

    /**
     * 属性
     */
    protected function attributes(): array
    {
        return [
            'button_tag_id'  => 'TagId',
            'button_content' => '榜单按钮文案',
            'start_time'     => '榜单开始时间',
            'end_time'       => '榜单结束时间',
            'level'          => '轮次',
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
