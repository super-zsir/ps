<?php

namespace Imee\Controller\Validation\Operate\Activity;

use Imee\Comp\Common\Validation\Validator;

class TaskPlayIssuedAwardListValidation extends Validator
{
    protected function rules(): array
    {
        return [
            'object_id'  => 'integer|min:1',
            'bigarea_id' => 'integer|min:1',
            'act_id'     => 'required_with:top,integer|min:1',
            'top'        => 'integer|min:1',
            'cid'        => 'integer|min:1',
        ];
    }

    /**
     * 属性
     */
    protected function attributes(): array
    {
        return [
            'object_id'  => '用户UID',
            'bigarea_id' => '大区',
            'act_id'     => '活动ID',
            'top'        => '奖励档位',
            'cid'        => '物品ID',
        ];
    }

    /**
     * 提示信息
     */
    protected function messages(): array
    {
        return [
            'act_id.required_with' => '奖励档位存在时必须筛选活动ID'
        ];
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