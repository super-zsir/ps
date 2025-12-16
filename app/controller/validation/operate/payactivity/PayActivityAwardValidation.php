<?php

namespace Imee\Controller\Validation\Operate\Payactivity;

use Imee\Comp\Common\Validation\Validator;
use Imee\Models\Xs\XsTopUpActivityMgr;
use Imee\Models\Xs\XsTopUpActivityReward;

class PayActivityAwardValidation extends Validator
{
    protected function rules()
    {
        return [
            'bigarea_id'                                 => 'required|integer',
            'top_up_activity_id'                         => 'required|integer',
            'level_award_list'                           => 'required|array',
            'level_award_list.*.level'                   => 'required|integer|min:1|max:2147483647',
            'level_award_list.*.award_list'              => 'required|array',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'bigarea_id'                                 => '运营大区',
            'top_up_activity_id'                         => '活动ID',
            'level_award_list'                           => '充值门槛及奖励配置',
            'level_award_list.*.level'                   => '钻石门槛',
            'level_award_list.*.award_list'              => '奖励配置',
        ];
    }

    /**
     * 提示信息
     */
    protected function messages()
    {
        return [
            'award_list.*.icon.required_if' => '奖励类型为自定义时，预览图必填',
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