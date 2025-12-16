<?php

namespace Imee\Controller\Validation\Operate\Activity;

use Imee\Comp\Common\Validation\Validator;

class ActivityLuckGamePlayValidation extends Validator
{
    protected function rules(): array
    {
        return [
            'id'                                                 => 'integer',
            'title'                                              => 'required|string',
            'language'                                           => 'required|string',
            'bigarea_id'                                         => 'required|array',
            'time_offset'                                        => 'required',
            'start_time'                                         => 'required|date',
            'end_time'                                           => 'required|date|after:start_time',
            'data_period'                                        => 'required|integer',
            'lottery_consume'                                    => 'required|integer',
            'vision_type'                                        => 'required|integer',
            'score_source'                                       => 'required|array',
            'score_source.*.source_type'                         => 'required|integer',
            'score_source.*.scope'                               => 'required|array',
            'score_source.*.source_config'                       => 'required|array',
            'award_list'                                         => 'required|array',
            'award_list.*.type'                                  => 'required|integer',
            'award_list.*.num'                                   => 'required_if:award_list.*.type,7,9,17,14,1,4,18,26,27|integer',
            'award_list.*.days'                                  => 'required_if:award_list.*.type,5,3,16,8,7,19,18,22,26,27|integer',
            'award_list.*.id'                                    => 'required_if:award_list.*.type,14,1,5,16,8,3,7,19,18,22,26,27|integer',
            'award_list.*.exp_days'                              => 'required_if:award_list.*.type,7,26,27|integer',
            'award_list.*.content'                               => 'required_if:award_list.*.type,22|string',
            'award_list.*.give_type'                             => 'required_if:award_list.*.type,4,8,7,26,27|integer',
            'award_list.*.weight'                                => 'required|integer|min:0',
            'award_list.*.stock_type'                            => 'required|integer',
            'award_list.*.stock'                                 => 'required_if:award_list.*.stock_type,2|integer',
            'has_relate'                                         => 'required|integer',
            'has_be_related'                                     => 'required|integer',
            'relate_type'                                        => 'required_if:has_relate,1|integer',
            'relate_id'                                          => 'required_if:has_relate,1|integer',
            'relate_icon'                                        => 'required_if:has_relate,1|string',
            'button_desc'                                        => 'string',
            'vision_content_json'                                => 'required_if:vision_type,8|array',
            'vision_content_json.pop_bottom_color_vc'            => 'required_if:vision_type,8|string',
            'vision_content_json.main_text_color_vc'             => 'required_if:vision_type,8|string',
            'vision_content_json.corner_marker_bottom_color_vc'  => 'required_if:vision_type,8|string',
            'vision_content_json.button_text_color_vc'           => 'required_if:vision_type,8|string',
            'vision_content_json.module_bgc_color_vc'            => 'required_if:vision_type,8|string',
            'vision_content_json.title_bgc_color_vc'             => 'required_if:vision_type,8|string',
            'vision_content_json.pop_border_color_vc'            => 'required_if:vision_type,8|string',
            'vision_content_json.turntable_partition_color_vc'   => 'required_if:vision_type,8|string',
            'vision_content_json.bgc_img_vc'                     => 'required_if:vision_type,8|string',
            'vision_content_json.turntable_img_vc'               => 'required_if:vision_type,8|string',
            'vision_content_json.pointer_img_vc'                 => 'required_if:vision_type,8|string',
            'vision_content_json.barrage_img_vc'                 => 'required_if:vision_type,8|string',
            'vision_content_json.button_img_vc'                  => 'required_if:vision_type,8|string',
            'vision_content_json.pop_bottom2_color_vc'           => 'required_if:vision_type,9|string',
            'vision_content_json.main_text2_color_vc'            => 'required_if:vision_type,9|string',
            'vision_content_json.corner_marker_bottom2_color_vc' => 'required_if:vision_type,9|string',
            'vision_content_json.title_bgc2_color_vc'            => 'required_if:vision_type,9|string',
            'vision_content_json.pop_border2_color_vc'           => 'required_if:vision_type,9|string',
            'vision_content_json.module_bgc2_color_vc'           => 'required_if:vision_type,9|string',
            'vision_content_json.bgc2_img_vc'                    => 'required_if:vision_type,9|string',
            'vision_content_json.barrage2_img_vc'                => 'required_if:vision_type,9|string',
            'vision_content_json.twisted_egg_one2_img_vc'        => 'required_if:vision_type,9|string',
            'vision_content_json.twisted_egg_two2_img_vc'        => 'required_if:vision_type,9|string',
            'vision_content_json.rotation_btn2_img_vc'           => 'required_if:vision_type,9|string',
            'vision_content_json.task_button_img_vc'             => 'required_if:vision_type,8,9|string',
        ];
    }

    /**
     * 属性
     */
    protected function attributes(): array
    {
        return [
            'title'                                              => '活动标题',
            'language'                                           => '活动语言',
            'bigarea_id'                                         => '运营大区',
            'time_offset'                                        => '统计区时',
            'start_time'                                         => '活动开始时间',
            'end_time'                                           => '活动结束时间',
            'data_period'                                        => '数据保留天数',
            'lottery_consume'                                    => '单次抽奖消耗积分',
            'vision_type'                                        => '活动视觉',
            'score_source'                                       => '抽奖活动来源',
            'score_source.*.source_type'                         => '积分类型',
            'score_source.*.scope'                               => '积分范围',
            'score_source.*.source_config'                       => '积分统计方式及配置',
            'award_list'                                         => '抽奖奖品及预期配置',
            'award_list.*.type'                                  => '奖励类型',
            'award_list.*.num'                                   => '奖励份数',
            'award_list.*.days'                                  => '奖励天数',
            'award_list.*.id'                                    => '奖励ID',
            'award_list.*.exp_days'                              => '奖励有效天数',
            'award_list.*.weight'                                => '奖励权重',
            'award_list.*.stock_type'                            => '奖励抽出上限类型',
            'award_list.*.give_type'                             => '是否可赠送',
            'award_list.*.stock'                                 => '奖励抽出上限数量',
            'has_relate'                                         => '是否关联其他玩法',
            'has_be_related'                                     => '是否被榜单模版关联',
            'relate_type'                                        => '关联模版类型',
            'relate_id'                                          => '关联的活动ID',
            'relate_icon'                                        => '关联活动的跳转入口icon',
            'button_desc'                                        => '规则内容',
            'vision_content_json'                                => '视觉配置',
            'vision_content_json.pop_bottom_color_vc'            => '浮窗底色',
            'vision_content_json.main_text_color_vc'             => '主字色',
            'vision_content_json.corner_marker_bottom_color_vc'  => '角标底色',
            'vision_content_json.button_text_color_vc'           => '按钮字色',
            'vision_content_json.module_bgc_color_vc'            => '模块背景色',
            'vision_content_json.title_bgc_color_vc'             => '标题背景色',
            'vision_content_json.turntable_partition_color_vc'   => '转盘分区色',
            'vision_content_json.pop_border_color_vc'            => '弹窗边框色',
            'vision_content_json.bgc_img_vc'                     => '背景切图',
            'vision_content_json.turntable_img_vc'               => '奖励框切图',
            'vision_content_json.pointer_img_vc'                 => '钻石框切图',
            'vision_content_json.barrage_img_vc'                 => '弹幕切图',
            'vision_content_json.button_img_vc'                  => '按钮切图',
            'vision_content_json.pop_bottom2_color_vc'           => '浮窗底色',
            'vision_content_json.main_text2_color_vc'            => '主字色',
            'vision_content_json.corner_marker_bottom2_color_vc' => '角标底色',
            'vision_content_json.title_bgc2_color_vc'            => '标题背景色',
            'vision_content_json.pop_border2_color_vc'           => '弹窗边框色',
            'vision_content_json.module_bgc2_color_vc'           => '模块背景色',
            'vision_content_json.bgc2_img_vc'                    => '背景切图',
            'vision_content_json.barrage2_img_vc'                => '弹幕切图',
            'vision_content_json.twisted_egg_one2_img_vc'        => '扭蛋1',
            'vision_content_json.twisted_egg_two2_img_vc'        => '扭蛋2',
            'vision_content_json.rotation_btn2_img_vc'           => '旋转按钮',
            'vision_content_json.task_button_img_vc'             => '任务底框切图',
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