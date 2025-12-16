<?php

namespace Imee\Controller\Validation\Operate\Whitelist;

use Imee\Comp\Common\Validation\Validator;
use Imee\Models\Xsst\BmsWhitelistSetting;

class WhiteListValidation extends Validator
{
    protected function rules()
    {
        return [
            'name' => 'required|string',
            'type' => 'required|string|in:'.implode(',', array_keys(BmsWhitelistSetting::$table)),
            'description' => 'required|string',
            'value' => 'required|integer|min:0|max:255',
            'uid' => 'required|array',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'name' => '白名单名称',
            'type' => '白名单类型',
            'description' => '白名单描述',
            'value' => '类型值',
            'uid' => '管理员',
        ];
    }

    /**
     * 提示信息
     */
    protected function messages()
    {
        return [
            'type.in' => '白名单类型不存在'
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
                'code' => 0,
                'msg' => '',
                'total' => 1,
                'data' => [
                ],
            ],
        ];
    }
}