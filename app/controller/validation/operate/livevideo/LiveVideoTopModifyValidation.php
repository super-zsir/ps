<?php

namespace Imee\Controller\Validation\Operate\Livevideo;

use Imee\Comp\Common\Validation\Validator;

class LiveVideoTopModifyValidation extends Validator
{
    protected function rules()
    {
        return [
            'id'         => 'required|integer',
            'uid'        => 'required|integer',
            'area_id'    => 'required|integer',
            'start_time' => 'required|string',
            'end_time'   => 'required|string|after:start_tine',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'id'         => 'id',
            'uid'        => '房主uid',
            'area_id'    => '运营大区',
            'start_time' => '开始时间',
            'end_time'   => '结束时间',
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
                'code'    => 0,
                'msg'     => '',
                'data'    => null,
            ],
        ];
    }
}