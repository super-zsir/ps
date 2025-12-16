<?php

namespace Imee\Controller\Validation\Operate\Pretty\UserCustomize;

use Imee\Comp\Common\Validation\Validator;

class CreateValidation extends Validator
{
    protected function rules()
    {
        return [
            'uid_str' => 'required',
            'customize_pretty_id' => 'required|integer',
            'pretty_validity_day' => 'required|integer',
            'qualification_expire_day' => 'required|integer',
            'remark' => '',
            'give_type' => 'integer|in:0,1',
            'send_num' => 'integer|max:10'
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'uid_str' => 'uid',
            'customize_pretty_id' => '类型',
            'pretty_validity_day' => '靓号有效天数',
            'qualification_expire_day' => '资格使用有效天数',
            'remark' => '备注',
            'send_num' => '发放数量'
        ];
    }

    /**
     * 提示信息
     */
    protected function messages()
    {
        return [
            'send_num.max' => '发放数量最大值为10次'
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
