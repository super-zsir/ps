<?php

namespace Imee\Controller\Validation\Operate\Activity;

use Imee\Comp\Common\Validation\Validator;

class PayActivityGiftBagLogListValidation extends Validator
{
    protected function rules(): array
    {
        return [
            'uid'                => 'integer|min:1',
            'bigarea_id'         => 'integer|min:1',
            'top_up_activity_id' => 'required_with:diamond,integer|min:1',
            'diamond'            => 'integer|min:1',
            'cid'                => 'integer|min:1',
        ];
    }

    /**
     * 属性
     */
    protected function attributes(): array
    {
        return [
            'uid'                => '用户UID',
            'bigarea_id'         => '大区',
            'top_up_activity_id' => '活动ID',
            'diamond'            => '达标钻石门槛',
            'cid'                => '物品ID',
        ];
    }

    /**
     * 提示信息
     */
    protected function messages(): array
    {
        return [
            'top_up_activity_id.required_with' => '达标钻石门槛存在时必须筛选活动ID'
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