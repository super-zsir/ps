<?php

namespace Imee\Controller\Validation\Operate\Activity;

use Imee\Comp\Common\Validation\Validator;

/**
 * ButtonList管理
 */
class ButtonListCreateValidation extends Validator
{
    protected function rules(): array
    {
        return [
            'button_tag_id' => 'required|integer',
            'rank_tag'      => 'required|integer',
            'rank_list_num' => 'required|integer',
        ];
    }

    /**
     * 属性
     */
    protected function attributes(): array
    {
        return [
            'button_tag_id' => 'TagId',
            'rank_tag'      => '榜单类型',
            'rank_list_num' => '榜单显示名额',
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
