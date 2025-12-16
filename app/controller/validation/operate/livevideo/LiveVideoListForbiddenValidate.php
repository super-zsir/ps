<?php

namespace Imee\Controller\Validation\Operate\Livevideo;

use Imee\Comp\Common\Validation\Validator;
use Imee\Models\Xs\XsBanRoomLog;

class LiveVideoListForbiddenValidate extends Validator
{
    protected function rules()
    {
        return [
            'rid'      => 'required|integer|min:1',
            'deleted'  => 'required|integer|in:' . implode(',', array_keys(XsBanRoomLog::$deletedMap)),
            'duration' => 'required_if:deleted,1|integer|in:' . implode(',', array_keys(XsBanRoomLog::$durationMap)),
            'reason'   => 'required_if:deleted,1|integer|in:' . implode(',', array_keys(XsBanRoomLog::$reasonMap)),
            'remark'   => 'string|max:200',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'rid'      => 'rid',
            'deleted'  => '操作',
            'duration' => '封禁时长',
            'reason'   => '原因',
            'remark'   => '备注',
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