<?php

namespace Imee\Controller\Validation\Operate\Vipsend;

use Imee\Comp\Common\Validation\Validator;
use Imee\Models\Xs\XsVipRecord;
use Imee\Models\Xsst\BmsVipSendDetail;

class LimitCreateValidation extends Validator
{
    protected function rules()
    {
        return [
            'bigarea_id' => 'required|integer',
            'vip'        => 'required|integer',
            'period'     => 'required|integer',
            'num'        => 'required|integer|min:1|max:99',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'bigarea_id' => '大区',
            'vip'        => 'VIP等级',
            'period'     => '周期',
            'num'        => '限制发放次数',
        ];
    }

    /**
     * 提示信息
     */
    protected function messages()
    {
        return [
            'num.*' => '限制发放次数必须为1-99之间的整数，请检查后重试'
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
                'code'    => 0,
                'msg'     => '',
                'total'   => 1,
                'data'    => [
                ],
            ],
        ];
    }
}