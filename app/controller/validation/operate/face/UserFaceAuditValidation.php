<?php

namespace Imee\Controller\Validation\Operate\Face;

use Imee\Comp\Common\Validation\Validator;

class UserFaceAuditValidation extends Validator
{
    protected function rules()
    {
        return [
            'id'       => 'required|integer',
            'type'     => 'required|integer',
            'uid'      => 'required|integer',
            'dateline' => 'required|date',
            'status'   => 'required|integer',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'id'       => 'ID',
            'type'     => '操作类型',
            'uid'      => '用户UID',
            'dateline' => '提交认证时间',
            'status'   => '审核状态',
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
                'total' => 1,
                'data' => [
                ],
            ],
        ];
    }
}