<?php

namespace Imee\Controller\Validation\Operate\Pretty\UserCustomize;

use Imee\Comp\Common\Validation\Validator;

class ImportValidation extends Validator
{
    protected function rules()
    {
        return [
            'data' => 'required|array',
            'data.*.uid' => 'required|integer',
            'data.*.customize_pretty_id' => 'required|integer',
            'data.*.pretty_validity_day' => 'required|integer',
            'data.*.qualification_expire_day' => 'required|integer',
            'data.*.give_type' => 'integer|in:0,1',
            'data.*.remark' => '',
            'data.*.send_num' => 'integer|max:10'
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'uid' => 'UID',
            'customize_pretty_id' => '自选靓号类型',
            'pretty_validity_day' => '靓号有效天数',
            'qualification_expire_day' => '资格使用到期时间',
            'remark' => '备注',
            'send_num' => '发放数量',
        ];
    }

    /**
     * 提示信息
     */
    protected function messages()
    {
        return [
            'data.*.send_num.max' => '发放数量最大值为10次'
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
