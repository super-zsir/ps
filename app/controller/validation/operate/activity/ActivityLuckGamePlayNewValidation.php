<?php

namespace Imee\Controller\Validation\Operate\Activity;

use Imee\Comp\Common\Validation\Validator;
use Imee\Models\Config\BbcRankButtonList;

class ActivityLuckGamePlayNewValidation extends Validator
{
    protected function rules(): array
    {
        return [
            'id'                             => 'integer',
            'title'                          => 'required|string',
            'language'                       => 'required|string',
            'bigarea_id'                     => 'required|array',
            'time_offset'                    => 'required',
            'start_time'                     => 'required|date',
            'end_time'                       => 'required|date|after:start_time',
            'components_number'              => 'required|integer',
            'data_period'                    => 'required|integer',
            'vision_type'                    => 'required|integer',
            'score_source'                   => 'required|array',
            'score_source.*.source_type'     => 'required|integer',
            'score_source.*.scope'           => 'required|array',
            'score_source.*.source_config'   => 'required|array',
            'award_config'                   => 'required|array|min:1|max:3',
            'award_config.*.button_content'  => 'required|string',
            'award_config.*.lottery_consume' => 'required|integer|min:1',
            'award_config.*.is_score'        => 'required|integer|in:' . implode(',', array_keys(BbcRankButtonList::$isScoreMap)),
            'award_config.*.score_min'       => 'required_if:award_config.*.is_score,2|integer|min:1',
            'award_config.*.award_list'      => 'required|array|min:5|max:10',
            'has_relate'                     => 'required|integer',
            'has_be_related'                 => 'required|integer',
            'relate_type'                    => 'required_if:has_relate,1|integer',
            'relate_id'                      => 'required_if:has_relate,1|integer',
            'relate_icon'                    => 'required_if:has_relate,1|string',
            'button_desc'                    => 'string',
        ];
    }

    /**
     * 属性
     */
    protected function attributes(): array
    {
        return [
            'title'                          => '活动标题',
            'language'                       => '活动语言',
            'bigarea_id'                     => '运营大区',
            'time_offset'                    => '统计区时',
            'start_time'                     => '活动开始时间',
            'end_time'                       => '活动结束时间',
            'data_period'                    => '数据保留天数',
            'award_config'                   => '幸运奖品及预期配置',
            'award_config.*.button_content'  => 'tab标题',
            'award_config.*.lottery_consume' => '单次消耗积分',
            'award_config.*.is_score'        => '是否设置解锁门槛',
            'award_config.*.score_min'       => '资格解锁门槛数值',
            'award_config.*.award_list'      => '奖品配置',
            'vision_type'                    => '活动视觉',
            'score_source'                   => '抽奖活动来源',
            'score_source.*.source_type'     => '积分类型',
            'score_source.*.scope'           => '积分范围',
            'score_source.*.source_config'   => '积分统计方式及配置',
            'has_be_related'                 => '是否被榜单模版关联',
            'has_relate'                     => '是否关联其他玩法',
            'relate_type'                    => '关联模版类型',
            'relate_id'                      => '关联的活动ID',
            'relate_icon'                    => '关联活动的跳转入口icon',
            'button_desc'                    => '规则内容',
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