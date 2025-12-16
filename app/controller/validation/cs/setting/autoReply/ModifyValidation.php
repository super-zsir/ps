<?php

namespace Imee\Controller\Validation\Cs\Setting\AutoReply;

use Imee\Comp\Common\Validation\Validator;

class ModifyValidation extends Validator
{
    protected function rules()
    {
        return [
            'id'               => 'integer',
            'tag'              => 'required|string',
            'subject'          => 'required|string',
            'answer'           => 'required|string',
            'type'             => 'integer',
            'guide_to_service' => 'integer|in:0,1',
            'hot'              => 'integer|between:0,1000',
            'language'         => 'required|string'
        ];
    }

    protected function attributes()
    {
        return [
            'tag'              => '标签',
            'subject'          => '标题',
            'answer'           => '答案',
            'type'             => '问题类型',
            'guide_to_service' => '是否转人工',
            'hot'              => '排序',
            'language'         => '语言',
        ];
    }

    protected function messages()
    {
        return [];
    }

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