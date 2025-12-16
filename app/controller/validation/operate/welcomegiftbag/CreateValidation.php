<?php

namespace Imee\Controller\Validation\Operate\Welcomegiftbag;

use Imee\Comp\Common\Validation\Validator;
use Imee\Models\Xs\XsGiftBag;

class CreateValidation extends Validator
{
    protected function rules()
    {
        $gbColl = array_merge(XsGiftBag::$commodityList, XsGiftBag::$medalList);
        $gbColl = array_merge($gbColl, XsGiftBag::$backgroundList);

        $gbColl2 = array_merge($gbColl, [XsGiftBag::REWARD_PRETTY_UID]);
        $gbColl3 = array_merge($gbColl2, XsGiftBag::$couponList);
        $gbColl = array_merge($gbColl3, [XsGiftBag::REWARD_VIP]);
        return [
            'reward'                => 'required|array',
            'reward.*.gb_type'      => 'required|integer|in:' . implode(',', array_keys(XsGiftBag::$displayRewardType)),
            'reward.*.num'          => 'integer|min:1|required_if:reward.*.gb_type,' . implode(',', $gbColl3),
            'reward.*.reward_id'    => 'string|required_if:reward.*.gb_type,' . implode(',', $gbColl),
            'reward.*.vip_day'      => 'integer|required_if:reward.*.gb_type,' . XsGiftBag::REWARD_VIP,
            'reward.*.add_vip_type' => 'integer|required_if:reward.*.gb_type,' . XsGiftBag::REWARD_VIP,
            'cn'                    => 'required|max:20',
            'en'                    => 'required',
            'ar'                    => '',
            'ms'                    => '',
            'id'                    => '',
            'vi'                    => '',
            'hi'                    => '',
            'bn'                    => '',
            'ur'                    => '',
            'tl'                    => '',
            'remark'                => 'string|max:20',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'reward' => '物品',
            'remark' => '备注',
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