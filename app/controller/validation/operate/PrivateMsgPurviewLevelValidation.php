<?php

namespace Imee\Controller\Validation\Operate;

use Imee\Comp\Common\Validation\Validator;

class PrivateMsgPurviewLevelValidation extends Validator
{
    protected function rules()
    {
        return [
            'id'            => 'required|integer',
            'text_level'    => 'required|integer|min:0',
            'voice_level'   => 'required|integer|min:0',
            'img_level'     => 'required|integer|min:0',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'id'            => 'ID',
            'text_level'    => '文本消息限制',
            'voice_level'   => '语音消息限制',
            'img_level'     => '图片消息限制',
        ];
    }

    /**
     * 提示信息
     */
    protected function messages()
    {
        return [
            'text_level.min'    => '文本消息限制不能为负数',
            'voice_level.min'   => '语音消息限制不能为负数',
            'img_level.min'     => '图片消息限制不能为负数',
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
                'data' => null,
            ],
        ];
    }
}