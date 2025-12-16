<?php

namespace Imee\Controller\Validation\Operate;

use Imee\Comp\Common\Validation\Validator;

class MultilangValidation extends Validator
{
    protected function rules()
    {
        return [
            'key'      => 'required|regex:/^(common_[a-z0-9_]+|[a-z]+(_[a-z0-9]+)+)$/',
            'version'  => 'required|regex:/^\\d+\\.\\d+\\.\\d+$/',
            'zh_CN'    => 'required|string',
            'zh_TW'    => 'required|string',
            'en'       => 'required|string',
            // 其他语言可选
        ];
    }

    protected function attributes()
    {
        return [
            'key'     => 'Key',
            'version' => '版本号',
            'zh_CN'   => '中文简体',
            'zh_TW'   => '中文繁体',
            'en'      => '英文',
        ];
    }

    protected function messages()
    {
        return [
            'key.required'     => 'key未填写',
            'key.regex'        => 'key的格式必须为aaa_bbb或aaa_11,不限长度',
            'version.required' => '版本号未填写',
            'version.regex'    => '版本号格式错误，正确格式为X.X.X,X=数字',
            'zh_CN.required'   => '中文简体未填写',
            'zh_TW.required'   => '中文繁体未填写',
            'en.required'      => '英文未填写',
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