<?php

namespace Imee\Controller\Validation\Operate\Activity;

use Imee\Comp\Common\Validation\Validator;

class ActivityTaskGamePlayValidation extends Validator
{
    protected function rules(): array
    {
        return [
            'id'                           => 'integer',
            'title'                        => 'required|string',
            'language'                     => 'required|string',
            'bigarea_id'                   => 'required|array',
            'time_offset'                  => 'required',
            'type'                         => 'required|string',
            'vision_type'                  => 'required_if:type,task|integer',
            'tag_list_type'                => 'required|integer',
            'button_tag_id'                => 'required_with:id|integer',
            'button_list_id'               => 'required_with:id|integer',
            'start_time'                   => 'required|date',
            'end_time'                     => 'required|date|after:start_time',
            'score_source'                 => 'required|array',
            'divide_track'                 => 'required|integer',
            'divide_type'                  => 'required_if:divide_track,1|integer',
            'broker_distance_start_day'    => 'required_if:divide_track,1|integer|between:1,90',
            'score_source.*.source_type'   => 'required|integer',
            'score_source.*.scope'         => 'required',
            'score_source.*.source_config' => 'required|array',
            'rank_object'                  => 'required|integer',
            'sub_rank_object'              => 'required_if:rank_object,1,5|integer',
            'rank_award'                   => 'required|array',
            'data_period'                  => 'required|integer|min:1',
            'has_be_related'               => 'required_if:type,task|integer',
        ];
    }

    /**
     * 属性
     */
    protected function attributes(): array
    {
        return [
            'title'                        => '活动标题',
            'language'                     => '活动语言',
            'bigarea_id'                   => '运营大区',
            'time_offset'                  => '统计区时',
            'tag_list_type'                => '任务重复周期',
            'type'                         => '活动类型',
            'vision_type'                  => '活动视觉',
            'has_be_related'               => '是否被其他模版关联',
            'start_time'                   => '活动开始时间',
            'end_time'                     => '活动结束时间',
            'rank_object'                  => '活动对象',
            'sub_rank_object'              => '任务对象',
            'button_tag_id'                => 'button_tag_id',
            'button_list_id'               => 'button_list_id',
            'score_source'                 => '积分来源',
            'score_source.*.source_type'   => '积分类型',
            'score_source.*.scope'         => '统计范围',
            'score_source.*.source_config' => '积分统计方式',
            'rank_award'                   => '任务档位及奖励配置',
            'data_period'                  => '数据保留天数',
            'divide_track'                 => '是否限制任务人群',
            'divide_type'                  => '依据',
            'broker_distance_start_day'    => '具体要求',
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