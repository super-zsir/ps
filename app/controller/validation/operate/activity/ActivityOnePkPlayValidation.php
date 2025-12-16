<?php

namespace Imee\Controller\Validation\Operate\Activity;

use Imee\Comp\Common\Validation\Validator;

class ActivityOnePkPlayValidation extends Validator
{
    protected function rules(): array
    {
        return [
            'language'                     => 'required|string',
            'bigarea_id'                   => 'required|array',
            'time_offset'                  => 'required',
            'active_start_time'            => 'required|date',
            'active_end_time'              => 'required|date|after:active_start_time',
            'onepk_obj'                    => 'required|integer',
            'onepk_object'                 => 'required|array',
            'onepk_object.*.onepk_objid_1' => 'required|integer',
            'onepk_object.*.onepk_objid_2' => 'required|integer',
            'onepk_object.*.start_time'    => 'required|date|after:active_start_time',
            'onepk_object.*.end_time'      => 'required|date|before:active_end_time|after:onepk_object.*.start_time',
            'room_support'                 => 'required|integer',
            'rank_score_config'            => 'required|array',
            'rank_score_config.*.type'     => 'required|integer',
            'title'                        => 'required|string',
            'data_period'                  => 'required|integer|min:0',
            'banner_homepage_img'          => 'required|string',
        ];
    }

    /**
     * 属性
     */
    protected function attributes(): array
    {
        return [
            'language'                     => '活动语言',
            'bigarea_id'                   => '运营大区',
            'active_start_time'            => '活动开始时间',
            'active_end_time'              => '活动结束时间',
            'time_offset'                  => '统计区时',
            'onepk_obj'                    => 'pk对象',
            'onepk_object'                 => 'PK配置',
            'onepk_object.*.onepk_objid_1' => 'PKID1',
            'onepk_object.*.onepk_objid_2' => 'PKID2',
            'onepk_object.*.start_time'    => '对战开始时间',
            'onepk_object.*.end_time'      => '对战结束时间',
            'room_support'                 => '统计范围',
            'rank_score_config'            => '积分统计方式配置',
            'rank_score_config.*.type'     => '积分统计方式',
            'title'                        => '活动标题',
            'data_period'                  => '数据保留天数',
            'banner_homepage_img'          => '头图',
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