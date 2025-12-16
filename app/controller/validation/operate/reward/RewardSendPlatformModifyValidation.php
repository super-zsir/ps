<?php

namespace Imee\Controller\Validation\Operate\Reward;

use Imee\Comp\Common\Validation\Validator;
use Imee\Models\Xsst\XsstRewardTemplate;

class RewardSendPlatformModifyValidation extends Validator
{
    protected function rules()
    {
        return [
            'id'     => 'required|integer',
            'name'   => 'required|string',
            'status' => 'required|integer|in:' . implode(',', array_keys(XsstRewardTemplate::$statusMap)),
            'remark' => 'string',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'id'     => 'ID',
            'name'   => '发放模版名称',
            'status' => '状态',
            'remark' => '备注',
        ];
    }

    /**
     * 提示信息
     */
    protected function messages()
    {
        return [];
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