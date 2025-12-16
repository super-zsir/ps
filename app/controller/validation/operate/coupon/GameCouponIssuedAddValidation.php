<?php

namespace Imee\Controller\Validation\Operate\Coupon;


use Imee\Comp\Common\Validation\Validator;
use Imee\Models\Xs\XsstCouponIssued;

class GameCouponIssuedAddValidation extends Validator
{

    protected function rules()
    {
        return [
            'bigarea_id' => 'required|integer',
            'uid' => 'required|integer',
            'coupon_id' => 'required|integer',
            'num' => 'required|integer|max:1000',
            'expire_time' => 'required|integer|in:' . implode(',', array_keys(XsstCouponIssued::$expire)),
            'note' => 'string',
        ];
    }

    protected function attributes()
    {
        return [
            'bigarea_id' => '优惠券大区',
            'uid' => '用户ID',
            'coupon_id' => '游戏优惠券ID',
            'num' => '数量',
            'expire_time' => '有效期',
            'note' => '备注',
        ];
    }

    protected function response()
    {
        return [
            'result' => [
                'success' => true,
                'code' => 0,
                'msg' => '',
                'data' => true,
            ],
        ];
    }

    protected function messages()
    {
        return [];
    }
}