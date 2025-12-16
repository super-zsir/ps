<?php

namespace Imee\Controller\Validation\Operate\Viprecord;

use Imee\Comp\Common\Validation\Validator;
use Imee\Models\Xs\XsVipRecord;
use Imee\Models\Xsst\BmsVipSendDetail;

class CreateValidation extends Validator
{
    protected function rules()
    {
        return [
            'vip_level' => 'required|integer|in:'. implode(',', array_keys(BmsVipSendDetail::$displayVipLevel)),
            'vip_day' => 'required|integer|in:' . implode(',', BmsVipSendDetail::$allowDays),
            'uids' => 'required|string',
            'type' => 'integer|in:' . implode(',', array_keys(BmsVipSendDetail::$giveTypeMaps)),
            'remark' => 'required|string',
            'send_num' => 'integer|min:1|max:100',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'vip_level' => 'VIP等级',
            'vip_day' => 'VIP天数',
            'uids' => 'UID',
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
            'send_num.*'=>'VIP发放数量必须为1-100之间的整数，请检查后重试'
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