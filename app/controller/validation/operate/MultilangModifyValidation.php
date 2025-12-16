<?php

namespace Imee\Controller\Validation\Operate;

use Imee\Comp\Common\Validation\Validator;

class MultilangModifyValidation extends Validator
{
    protected function rules()
    {
        return [
            //'rid'     => 'required|integer',
            'key'     => 'required',
            'version' => 'required',
            'zh_cn'   => 'required|string',
            'cn'      => 'required|string',
            'en'      => 'required|string',
            // 其他语言可选
        ];
    }

    protected function attributes()
    {
        return [
            'rid'     => '记录ID',
            'key'     => 'Key',
            'version' => '版本号',
            'zh_cn'   => '中文简体',
            'cn'      => '中文繁体',
            'en'      => '英文',
        ];
    }

    protected function messages()
    {
        return [
            'rid.required'     => '记录ID未填写',
            'rid.integer'      => '记录ID格式错误',
            'key.required'     => 'key未填写',
            'version.required' => '版本号未填写',
            'version.regex'    => '版本号格式错误，正确格式为X.X.X,X=数字',
            'zh_cn.required'   => '中文简体未填写',
            'cn.required'      => '中文繁体未填写',
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