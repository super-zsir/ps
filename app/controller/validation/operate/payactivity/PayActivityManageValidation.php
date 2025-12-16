<?php

namespace Imee\Controller\Validation\Operate\Payactivity;

use Imee\Comp\Common\Validation\Validator;
use Imee\Models\Xs\XsTopUpActivityMgr;

class PayActivityManageValidation extends Validator
{
    protected function rules()
    {
        return [
            'bigarea_id' => 'required|integer',
            'status'     => 'required|integer|in:' . implode(',', [XsTopUpActivityMgr::STATUS_OFF, XsTopUpActivityMgr::STATUS_ON]),
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'bigarea_id' => '大区',
            'status'     => '状态'
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
                'total'   => 1,
                'data'    => [
                ],
            ],
        ];
    }
}