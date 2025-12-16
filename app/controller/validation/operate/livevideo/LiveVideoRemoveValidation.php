<?php

namespace Imee\Controller\Validation\Operate\Livevideo;

use Imee\Comp\Common\Validation\Validator;

class LiveVideoRemoveValidation extends Validator
{
    protected function rules()
    {
        return [
            'uid'     => 'required|integer',
            'area_id' => 'required|integer',
            'minutes' => 'required|integer|min:1|max:99999',
            'reason'  => 'required|string|max:255',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'uid'     => '房主uid',
            'area_id' => '运营大区',
            'minutes' => '移除时长(分钟)',
            'reason'  => '移除原因',
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