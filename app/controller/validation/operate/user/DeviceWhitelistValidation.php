<?php

namespace Imee\Controller\Validation\Operate\User;

use Imee\Comp\Common\Validation\Validator;

class DeviceWhitelistValidation extends Validator
{
    protected function rules()
    {
        return [
            'device_type'    => 'required|integer',
            'mac'            => 'required|string',
            'whitelist_type' => 'required|integer',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'device_type'    => '设备类型',
            'mac'            => '设备号',
            'whitelist_type' => '白名单类型',
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
                'data' => null,
            ],
        ];
    }
}