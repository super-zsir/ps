<?php

namespace Imee\Controller\Validation\Operate\Reward;

use Imee\Comp\Common\Validation\Validator;
use Imee\Models\Xsst\XsstRewardTemplate;

class RewardSendPlatformCreateValidation extends Validator
{
    protected function rules()
    {
        return [
            'name'                         => 'required|string',
            'reward_list'                  => 'required|array',
            'reward_list.*.type'           => 'required|integer|in:' . implode(',', array_keys(XsstRewardTemplate::$rewardTypeMap)),
            'reward_list.*.id'             => 'required_if:reward_list.*.type,1,3,4,5,6,8,9,10,11|integer',
            'reward_list.*.vip_level'      => 'required_if:reward_list.*.type,2|integer',
            'reward_list.*.vip_days'       => 'required_if:reward_list.*.type,2|integer',
            'reward_list.*.valid_days'     => 'required_if:reward_list.*.type,3,4,5,6,7,8,9,11|integer|min:1',
            'reward_list.*.use_valid_days' => 'required_if:reward_list.*.type,3|integer|min:1',
            'reward_list.*.big_area'       => 'required_if:reward_list.*.type,10|integer|min:1',
            'reward_list.*.expire'         => 'required_if:reward_list.*.type,10|integer',
            'reward_list.*.send_num'       => 'required_if:reward_list.*.type,1,3,7,8,10|integer|min:1',
            'reward_list.*.give_type'      => 'required_if:reward_list.*.type,2,3,7|integer',
            'reward_list.*.content'        => 'required_if:reward_list.*.type,5|string',
            'limit_big_area'               => 'required|integer',
            'limit_object'                 => 'required|integer',
            'max_send_num'                 => 'required|integer',
            'remark'                       => 'string',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'name'                         => '发奖模版名称',
            'reward_list'                  => '奖励内容配置',
            'reward_list.*.type'           => '奖励类型',
            'reward_list.*.id'             => 'ID',
            'reward_list.*.vip_level'      => 'VIP等级',
            'reward_list.*.vip_days'       => 'VIP天数',
            'reward_list.*.valid_days'     => '有效天数',
            'reward_list.*.use_valid_days' => '资格有效天数',
            'reward_list.*.big_area'       => '大区',
            'reward_list.*.expire'         => '有效期',
            'reward_list.*.send_num'       => '发放数量',
            'reward_list.*.give_type'      => '可否转赠',
            'reward_list.*.content'        => '文案内容',
            'limit_big_area'               => '限制发放大区',
            'limit_object'                 => '限制发放对象',
            'max_send_num'                 => '30天最多发放次数',
            'remark'                       => '备注',
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