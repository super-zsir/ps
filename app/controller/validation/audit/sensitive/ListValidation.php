<?php

namespace Imee\Controller\Validation\Audit\Sensitive;

use Imee\Comp\Common\Validation\Validator;
use Imee\Service\Helper;

class ListValidation extends Validator
{
    protected function rules()
    {
        return [
            'page' => 'required|integer',
            'limit' => 'required|integer|between:1,1000',
            'sort' => 'string',
            'dir' => 'string|in:asc,desc',
            'type' => 'string',
            'subType' => 'string',
            'cond' => 'string',
            'deleted' => 'integer',

            'vague' => 'integer',
            'text' => 'string',
            'language' => 'string|in:' . implode(',', array_keys(Helper::getLanguageArr())),
            'danger' => 'integer',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'type' => '类型',
            'subType' => '二级分类',
            'cond' => '场景',
            'deleted' => '状态',
            'vague' => '是否拼音匹配',
            'text' => '敏感词',
            'language' => '语言',
            'danger' => '危险等级',
            
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
