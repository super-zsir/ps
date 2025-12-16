<?php

namespace Imee\Controller\Validation\Operate\Game;

use Imee\Comp\Common\Validation\Validator;

class GameLimitModifyValidation extends Validator
{
    protected function rules(): array
    {
        return [
            'game_center_id'        => 'required|integer',
            'first_recharge_limit'  => 'required|integer',
        ];
    }

    /**
     * 属性
     */
    protected function attributes(): array
    {
        return [
            'game_center_id'        => '玩法ID',
            'first_recharge_limit'  => '是否首冲解决',
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
