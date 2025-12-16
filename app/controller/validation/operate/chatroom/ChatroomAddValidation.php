<?php

namespace Imee\Controller\Validation\Operate\Chatroom;

use Imee\Comp\Common\Validation\Validator;
use Imee\Models\Xs\XsChatroom;

class ChatroomAddValidation extends Validator
{
    protected function rules(): array
    {
        return [
            'uid'                      => 'required|integer',
            'property'                 => 'required|string|in:' . implode(',', array_keys(XsChatroom::$propertyFormArray)),
            'sex'                      => 'required|string|in:' . implode(',', array_keys(XsChatroom::$sexMap)),
            'name'                     => 'required|string',
            'room_factory_type'        => 'required|string',
            'fixed_tag_id'             => 'required|integer',
            'settlement_channel'       => 'required|string',
            'salary_type'              => 'required|string',
            'icon'                     => 'string',
            'bicon'                    => 'string',
            'description'              => 'string',
            'language'                 => 'required|string',
            'area'                     => 'string',
            'switching_time'           => 'required|integer',
            'switching_start_time'     => 'required_if:switching_time,1|date',
            'switching_end_time'       => 'required_if:switching_time,1|date|after:switching_start_time',
            'theone_time'              => 'required|integer',
            'theone_start_time'        => 'required_if:theone_time,1|date',
            'theone_end_time'          => 'required_if:theone_time,1|date|after:theone_start_time',
            'vocal_concert'            => 'required|integer',
            'vocal_concert_week'       => 'required_if:vocal_concert,1|array',
            'vocal_concert_start_time' => 'required_if:vocal_concert,1|date',
            'vocal_concert_end_time'   => 'required_if:vocal_concert,1|date|after:vocal_concert_start_time',
        ];
    }

    /**
     * 属性
     */
    protected function attributes(): array
    {
        return [
            'rid'                      => '房间ID',
            'uid'                      => '创建者',
            'property'                 => '属性',
            'sex'                      => '性别',
            'name'                     => '名称',
            'room_factory_type'        => '模版',
            'fixed_tag_id'             => '外显标签',
            'settlement_channel'       => '结算频道',
            'salary_type'              => '结算类型',
            'icon'                     => '封面',
            'bicon'                    => '大图',
            'description'              => '玩法介绍',
            'language'                 => '语言',
            'area'                     => '地区',
            'switching_time'           => '拍卖模版切换时间',
            'switching_start_time'     => '拍卖模版开始时间',
            'switching_end_time'       => '拍卖模版结束时间',
            'theone_time'              => '非诚勿扰切换时间',
            'theone_start_time'        => '非诚勿扰开始时间',
            'theone_end_time'          => '非诚勿扰结束时间',
            'vocal_concert'            => '演唱会模板切换',
            'vocal_concert_week'       => '演唱会模板星期',
            'vocal_concert_start_time' => '演唱会模板开始时间',
            'vocal_concert_end_time'   => '演唱会模板结束时间',
        ];
    }

    /**
     * 提示信息
     */
    protected function messages(): array
    {
        return [];
    }

    /**
     * 返回数据结构
     */
    protected function response(): array
    {
        return [
            'result' => [
                'success' => true,
                'code'    => 0,
                'msg'     => '',
                'data'    => true,
            ],
        ];
    }
}