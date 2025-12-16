<?php

namespace Imee\Controller\Validation\Operate\Pretty\UserCustomize;

use Imee\Comp\Common\Validation\Validator;

class ModifyValidation extends Validator
{
    protected function rules()
    {
        return [
            'id' => 'required|integer',
            'pretty_validity_day' => 'required|integer',
            'qualification_expire_dateline' => 'required|date',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'uids' => 'uids',
            'customize_pretty_id' => '类型',
            'pretty_validity_day' => '靓号有效天数',
            'qualification_expire_day' => '资格使用有效天数',
            'remark' => '备注'
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
                'total' => 1,
                'data' => [
                ],
            ],
        ];
    }
}
