<?php

namespace Imee\Controller\Validation\Operate\Activity;

use Imee\Comp\Common\Validation\Validator;

class ActivityLuckGamePlayRewardValidation extends Validator
{
    protected function rules(): array
    {
        return [
            'id'                        => 'required|integer',
            'desc_path'                 => 'required_if:is_diamonds,1|string',
            'award_list'                => 'required|array',
            'award_list.*.list_id'      => 'required|integer',
            'award_list.*.award_number' => 'required|integer|min:1',
            'award_list.*.number'       => 'required|integer|min:1',
            'award_list.*.award_type'   => 'required|integer',
        ];
    }

    /**
     * 属性
     */
    protected function attributes(): array
    {
        return [
            'id'                        => '活动id',
            'desc_path'                 => '活动附件',
            'award_list'                => '奖励列表',
            'award_list.*.list_id'      => '榜单id',
            'award_list.*.award_number' => '奖励序号',
            'award_list.*.number'       => '补库存数量',
            'award_list.*.award_type'   => '奖励类型',
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