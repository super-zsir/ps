<?php

namespace Imee\Controller\Validation\Operate\User;

use Imee\Comp\Common\Validation\Validator;
use Imee\Service\Operate\User\DeviceWhitelistService;

class DeviceWhitelistBatchValidation extends Validator
{
    protected function rules()
    {
        return [
            'data.*.device_type'    => 'required|integer|in:1,2',
            'data.*.mac'            => 'required|string',
            'data.*.whitelist_type' => 'required|integer|in:' . implode(',', DeviceWhitelistService::getWhiteListValue()),
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'data.*.device_type'    => '设备类型',
            'data.*.mac'            => '设备号',
            'data.*.whitelist_type' => '白名单类型',
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