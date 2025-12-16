<?php

namespace Imee\Controller\Validation\Operate\Report;

use Imee\Comp\Common\Validation\Validator;

class MessageReportBannedValidation extends Validator
{
    protected function rules()
    {
        return [
            'id'            => 'required|integer',
            'device_id'     => 'string',
            'target_uid'    => 'required|integer',
            'ban_type'      => 'required|integer',
            'duration'      => 'required|integer',
            'is_ban_device' => 'required|integer',
            'sync_type'     => 'required|integer',
            'reason'        => 'required|string',
            'target'        => 'required|array',
            'notice_msg'    => 'string',
            'comment'       => 'string',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'id'            => 'ID',
            'device_id'     => '设备号',
            'target_uid'    => '被举报用户uid',
            'ban_type'      => '封禁',
            'duration'      => '封禁时长',
            'is_ban_device' => '是否封禁设备',
            'sync_type'     => '同步安全手机号',
            'reason'        => '原因',
            'target'        => '被举报用户信息',
            'notice_msg'    => '系用消息',
            'comment'       => '备注',
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