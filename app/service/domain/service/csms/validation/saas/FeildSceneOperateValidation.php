<?php

namespace Imee\Service\Domain\Service\Csms\Validation\Saas;

use Imee\Comp\Common\Validation\Validator;

class FeildSceneOperateValidation extends Validator
{
    protected function rules()
    {
        return [
            'fid' => 'required|int',
            'sid' => 'required|array',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'sid' => '服务商id',
            'fid' => '字段id',
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
