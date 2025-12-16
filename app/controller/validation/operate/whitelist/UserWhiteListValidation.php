<?php

namespace Imee\Controller\Validation\Operate\Whitelist;

use Imee\Comp\Common\Validation\Validator;

class UserWhiteListValidation extends Validator
{
    protected function rules()
    {
        return [
            'white_list_name' => 'required|integer|min:2',
            'uid' => 'required|integer',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'uid' => '用户UID',
            'white_list_name' => '白名单名称',
        ];
    }

    /**
     * 提示信息
     */
    protected function messages()
    {
        return [
            'white_list_name.min' => '白名单类型不正确',
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