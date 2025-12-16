<?php

namespace Imee\Controller\Validation\Cs\Setting\AutoReply;

use Imee\Comp\Common\Validation\Validator;

class ListValidation extends Validator
{
    protected function rules()
    {
        return [
            'page'    => 'required|integer',
            'limit'   => 'required|integer|between:1,1000',
            'sort'    => 'string',
            'dir'     => 'string|in:asc,desc',
            'tag'     => 'string',
            'subject' => 'string',
            'answer'  => 'string',
            'type'    => 'integer',
        ];
    }

    protected function attributes()
    {
        return [
            'tag'     => '标签内容',
            'subject' => '标题',
            'answer'  => '答案',
            'type'    => '会话分类',
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
                'total'   => 0,
                'msg'     => '',
                'data'    => [],
            ],
        ];
    }
}