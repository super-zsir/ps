<?php

namespace Imee\Controller\Validation\Operate;

use Imee\Comp\Common\Validation\Validator;

class MultilangBatchCreateValidation extends Validator
{
    protected function rules()
    {
        return [
            'list' => 'required|array',
            'list.*.key'      => 'required',
            'list.*.version'  => 'required',
            'list.*.zh_CN'    => 'required|string',
            'list.*.zh_TW'    => 'required|string',
            'list.*.en'       => 'required|string',
            // 其他语言可选
        ];
    }

    protected function attributes()
    {
        return [
            'list'     => '批量数据',
            'list.*.key'     => 'Key',
            'list.*.version' => '版本号',
            'list.*.zh_CN'   => '中文简体',
            'list.*.zh_TW'   => '中文繁体',
            'list.*.en'      => '英文',
        ];
    }

    protected function messages()
    {
        return [
            'list.required'     => '批量数据未填写',
            'list.*.key.required'     => 'key未填写',
            'list.*.key.regex'        => 'key的格式必须为aaa_bbb或aaa_11,不限长度',
            'list.*.version.required' => '版本号未填写',
            'list.*.version.regex'    => '版本号格式错误，正确格式为X.X.X,X=数字',
            'list.*.zh_CN.required'   => '中文简体未填写',
            'list.*.zh_TW.required'   => '中文繁体未填写',
            'list.*.en.required'      => '英文未填写',
        ];
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