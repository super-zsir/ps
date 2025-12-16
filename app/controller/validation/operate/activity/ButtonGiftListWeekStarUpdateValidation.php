<?php

namespace Imee\Controller\Validation\Operate\Activity;

use Imee\Comp\Common\Validation\Validator;

/**
 * 礼物榜ButtonList管理
 */
class ButtonGiftListWeekStarUpdateValidation extends Validator
{
    protected function rules(): array
    {
        return [
            'id'           => 'required|integer',
            'room_support' => 'integer',
        ];
    }

    /**
     * 属性
     */
    protected function attributes(): array
    {
        return [
            'id'           => 'ID',
            'room_support' => '房间类型',
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
