<?php

namespace Imee\Controller\Validation\Operate\Activity;

use Imee\Comp\Common\Validation\Validator;

/**
 * 礼物榜ButtonTag管理
 */
class ButtonGiftTagWeekStarUpdateValidation extends Validator
{
    protected function rules(): array
    {
        return [
            'id'                => 'required|integer',
            'rank_object'       => 'required|integer',
            'cycle_gift_id_num' => 'required|integer',
            'cycle_gift_id'     => 'required|string',
            'start_time'        => 'required|string',
            'cycle_type'        => 'required|integer',
        ];
    }

    /**
     * 属性
     */
    protected function attributes(): array
    {
        return [
            'id'                => 'TagId',
            'rank_object'       => '面向对象',
            'cycle_gift_id_num' => '单次循环的周星礼物数量',
            'cycle_gift_id'     => '单次循环的周星礼物ID',
            'start_time'        => '榜单开始时间',
            'cycle_type'        => '循环次数',
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
