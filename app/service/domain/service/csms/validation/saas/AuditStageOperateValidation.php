<?php

namespace Imee\Service\Domain\Service\Csms\Validation\Saas;

use Imee\Comp\Common\Validation\Validator;

class AuditStageOperateValidation extends Validator
{
    protected function rules()
    {
        return [
            'cid' => 'required|int',
            'stage' => 'required',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'cid' => '审核项ID',
            'stage' => '审核阶段 op初审 op2复审 op3质检',
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
                'code' => 0,
                'msg' => '',
                'data' => null,
            ],
        ];
    }
}
