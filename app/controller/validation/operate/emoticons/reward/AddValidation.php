<?php

namespace Imee\Controller\Validation\Operate\Emoticons\Reward;

use Imee\Comp\Common\Validation\Validator;

class AddValidation extends Validator
{
    protected function rules()
    {
        return [
            'emoticons_id' => 'required|integer',
            'reward_time'  => 'required|integer',
            'uids'         => 'required|string',
            'comment'      => 'string|max:200',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'emoticons_id' => '配置ID',
            'reward_time'  => '有效时长',
            'uids'         => '下发人群',
            'comment'      => '备注',
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
                'total'   => 1,
                'data'    => [
                ],
            ],
        ];
    }
}