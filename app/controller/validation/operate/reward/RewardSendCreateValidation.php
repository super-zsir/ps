<?php

namespace Imee\Controller\Validation\Operate\Reward;

use Imee\Comp\Common\Validation\Validator;
use Imee\Models\Xsst\XsstRewardSendTask;
use Imee\Models\Xsst\XsstRewardTemplate;

class RewardSendCreateValidation extends Validator
{
    protected function rules()
    {
        return [
            'uid_list'  => 'required|string',
            'tid'       => 'required|integer',
            'source'    => 'string',
            'is_notice' => 'required|integer|in:' . implode(',', array_keys(XsstRewardSendTask::$isNoticeMap)),
            'remark'    => 'string'
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'uid_list'  => 'UID',
            'tid'       => '奖励模版ID',
            'source'    => '发放来源',
            'is_notice' => '是否发送IM通知',
            'remark'    => '备注',
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