<?php

namespace Imee\Controller\Validation\Operate\User;

use Imee\Comp\Common\Validation\Validator;

class RegisterLoginLimitValidation extends Validator
{
    protected function rules()
    {
        return [
            'id'                              => 'required|integer',
            'device_register_num_limit'       => 'required|integer|min:0',
            'device_daily_register_num_limit' => 'required|integer|min:0',
            'device_daily_login_num_limit'    => 'required|integer|min:0',
            'device_weekly_login_num_limit'   => 'required|integer|min:0',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'id'                              => '运营大区',
            'device_register_num_limit'       => '设备注册账号数上限',
            'device_daily_register_num_limit' => '设备日注册账号数上限',
            'device_daily_login_num_limit'    => '设备日登陆账号数上限',
            'device_weekly_login_num_limit'   => '设备周登陆账号数上限',
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