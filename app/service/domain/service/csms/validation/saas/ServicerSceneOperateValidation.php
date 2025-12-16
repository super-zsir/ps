<?php

namespace Imee\Service\Domain\Service\Csms\Validation\Saas;

use Imee\Comp\Common\Validation\Validator;

class ServicerSceneOperateValidation extends Validator
{
    protected function rules()
    {
        return [
            'sid' => 'required|int',
            'mark' => 'required|string',
            'name' => 'required|string',
            'description' => 'required|string',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'sid' => '服务商id',
            'mark' => '场景标识',
            'name' => '场景名称',
            'description' => '简介'
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
