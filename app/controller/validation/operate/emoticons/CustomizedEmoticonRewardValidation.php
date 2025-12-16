<?php

namespace Imee\Controller\Validation\Operate\Emoticons;

use Imee\Comp\Common\Validation\Validator;

class CustomizedEmoticonRewardValidation extends Validator
{
    protected function rules()
    {
        return [
            'uid'                      => 'required|string',
            'customized_emoticon_id'   => 'required|integer|min:1',
            'valid_day'                => 'required|integer|min:1',
            'reason'                   => 'required|string|max:255',
        ];
    }

    protected function attributes()
    {
        return [
            'uid'                      => 'UID',
            'customized_emoticon_id'   => '表情ID',
            'valid_day'                => '生效时间',
            'reason'                   => '发放理由',
        ];
    }

    protected function messages()
    {
        return [
            'uid.required'                      => 'UID不能为空',
            'uid.string'                        => 'UID格式不正确，支持单个UID或逗号分隔的多个UID',
            'customized_emoticon_id.required'   => '表情ID不能为空',
            'customized_emoticon_id.integer'    => '表情ID必须是整数',
            'customized_emoticon_id.min'        => '表情ID必须大于0',
            'valid_day.required'                => '生效时间不能为空',
            'valid_day.integer'                 => '生效时间必须是整数',
            'valid_day.min'                     => '生效时间必须大于0',
            'reason.required'                   => '发放理由不能为空',
            'reason.string'                     => '发放理由必须是字符串',
            'reason.max'                        => '发放理由长度不能超过255个字符',
        ];
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