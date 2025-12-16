<?php

namespace Imee\Controller\Validation\Operate\Report;

use Imee\Comp\Common\Validation\Validator;

class MessageReportRejectValidation extends Validator
{
    protected function rules()
    {
        return [
            'id'         => 'required|integer',
            'device_id'  => 'string',
            'notice_msg' => 'string',
            'comment'    => 'string',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'id'         => 'ID',
            'device_id'  => '设备号',
            'notice_msg' => '系用消息',
            'comment'    => '备注',
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