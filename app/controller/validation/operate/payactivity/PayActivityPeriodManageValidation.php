<?php

namespace Imee\Controller\Validation\Operate\Payactivity;

use Imee\Comp\Common\Validation\Validator;
use Imee\Models\Xs\XsTopUpActivity;
use Imee\Models\Xs\XsTopUpActivityMgr;

class PayActivityPeriodManageValidation extends Validator
{
    protected function rules()
    {
        return [
            'title'                                       => 'required|string',
            'bigarea_id'                                  => 'required|integer',
            'language'                                    => 'required|string',
            'cycle_type'                                  => 'required|integer',
            'recharge_channels'                           => 'required|array',
            'start_time'                                  => 'required|date',
            'end_time'                                    => 'required_if:cycle_type,0,1|date',
            'status'                                      => 'required|int',
            'time_offset'                                 => 'required',
            'award_type'                                  => 'required|integer|in:' . implode(',', array_keys(XsTopUpActivity::$awardTypeMap)),
            'cycles'                                      => 'required_if:cycle_type,2|integer|min:1',
            'data_period'                                 => 'required|integer',
            'level_award_list'                            => 'required|array',
            'level_award_list.*.reward_level'             => 'required|integer',
            'level_award_list.*.level'                    => 'required|integer|min:1|max:2147483647',
            'level_award_list.*.award_list'               => 'required|array',
            'level_award_list.*.award_list.*.id'          => 'integer|min:1',
            'level_award_list.*.award_list.*.num'         => 'integer',
            'vision_content_json'                         => 'array',
            'vision_content_json.bgc_vc'                  => 'string',
            'vision_content_json.pop_bgc_vc'              => 'string',
            'vision_content_json.pop_border_vc'           => 'string',
            'vision_content_json.rule_text_color_vc'      => 'string',
            'vision_content_json.intro_text_color_vc'     => 'string',
            'vision_content_json.countdown_text_color_vc' => 'string',
            'vision_content_json.main_text_color_vc'      => 'string',
            'vision_content_json.point_text_color_vc'     => 'string',
            'vision_content_json.recharge_text_color_vc'  => 'string',
            'vision_content_json.head_img_vc'             => 'string',
            'vision_content_json.show_base_vc'            => 'string',
            'vision_content_json.rule_button_vc'          => 'string',
            'vision_content_json.countdown_bgc_vc'        => 'string',
            'vision_content_json.individual_bgc_vc'       => 'string',
            'vision_content_json.recharge_button_vc'      => 'string',
            'vision_content_json.award_show_bgc_vc'       => 'string',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'title'                                       => '活动标题',
            'bigarea_id'                                  => '大区',
            'language'                                    => '活动语言',
            'start_time'                                  => '活动开始时间',
            'end_time'                                    => '活动结束时间',
            'status'                                      => '状态',
            'cycle_type'                                  => '循环类型',
            'recharge_channels'                           => '充值渠道',
            'time_offset'                                 => '时区',
            'award_type'                                  => '发奖方式',
            'cycles'                                      => '循环周数',
            'data_period'                                 => '数据保留天数',
            'level_award_list'                            => '充值门槛及奖励配置',
            'level_award_list.*.reward_level'             => '钻石门槛index',
            'level_award_list.*.level'                    => '钻石门槛',
            'level_award_list.*.award_list'               => '奖励配置',
            'level_award_list.*.award_list.*.num'         => '奖励配置 数量必填正整数',
            'vision_content_json'                         => '活动视觉配置',
            'vision_content_json.bgc_vc'                  => '背景色',
            'vision_content_json.pop_bgc_vc'              => '弹窗背景色',
            'vision_content_json.pop_border_vc'           => '弹窗边框色',
            'vision_content_json.rule_text_color_vc'      => '规则字色',
            'vision_content_json.intro_text_color_vc'     => '引言字色',
            'vision_content_json.countdown_text_color_vc' => '倒计时字色',
            'vision_content_json.main_text_color_vc'      => '主要字色',
            'vision_content_json.point_text_color_vc'     => '重点字色',
            'vision_content_json.recharge_text_color_vc'  => '充值字色',
            'vision_content_json.head_img_vc'             => '头图',
            'vision_content_json.show_base_vc'            => '展示底座',
            'vision_content_json.rule_button_vc'          => '规则按钮',
            'vision_content_json.countdown_bgc_vc'        => '倒计时背景',
            'vision_content_json.individual_bgc_vc'       => '个人背景',
            'vision_content_json.recharge_button_vc'      => '充值按钮',
            'vision_content_json.award_show_bgc_vc'       => '奖励展示背景',
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