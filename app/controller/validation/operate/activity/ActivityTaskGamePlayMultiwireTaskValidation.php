<?php

namespace Imee\Controller\Validation\Operate\Activity;

use Imee\Comp\Common\Validation\Validator;

class ActivityTaskGamePlayMultiwireTaskValidation extends Validator
{
    protected function rules(): array
    {
        return [
            'id'                                           => 'integer',
            'title'                                        => 'required|string',
            'language'                                     => 'required|string',
            'bigarea_id'                                   => 'required|array',
            'time_offset'                                  => 'required',
            'start_time'                                   => 'required|date',
            'end_time'                                     => 'required|date|after:start_time',
            'data_period'                                  => 'required|integer|min:1',
            'has_relate'                                   => 'required|integer',
            'vision_content_json'                          => 'required|array',
            'vision_content_json.no_select_text_color_vc'  => 'required|string',
            'vision_content_json.select_text_color_vc'     => 'required|string',
            'vision_content_json.bgc_color_vc'             => 'required|string',
            'vision_content_json.module_bgc_color_vc'      => 'required|string',
            'vision_content_json.master_text_color_vc'     => 'required|string',
            'vision_content_json.progress_bar_color_vc'    => 'required|string',
            'vision_content_json.to_finish_btn_color_vc'   => 'required|string',
            'vision_content_json.get_btn_color_vc'         => 'required|string',
            'vision_content_json.float_bth_img_vc'         => 'required|string',
            'vision_content_json.select_btn_short_img_vc'  => 'required|string',
            'vision_content_json.select_btn_long_img_vc'   => 'required|string',
            'vision_content_json.reward_bottom_box_img_vc' => 'required|string',
        ];
    }

    /**
     * 属性
     */
    protected function attributes(): array
    {
        return [
            'title'                                        => '活动标题',
            'language'                                     => '活动语言',
            'bigarea_id'                                   => '运营大区',
            'time_offset'                                  => '统计区时',
            'has_relate'                                   => '是否与榜单活动关联',
            'start_time'                                   => '活动开始时间',
            'end_time'                                     => '活动结束时间',
            'data_period'                                  => '数据保留天数',
            'vision_content_json.no_select_text_color_vc'  => '未选中字色',
            'vision_content_json.select_text_color_vc'     => '选中字色',
            'vision_content_json.bgc_color_vc'             => '背景色',
            'vision_content_json.module_bgc_color_vc'      => '模块背景色',
            'vision_content_json.master_text_color_vc'     => '主字色',
            'vision_content_json.progress_bar_color_vc'    => '进度条色',
            'vision_content_json.to_finish_btn_color_vc'   => '去完成按钮色',
            'vision_content_json.get_btn_color_vc'         => '领取按钮色',
            'vision_content_json.float_bth_img_vc'         => '悬浮按钮切图',
            'vision_content_json.select_btn_short_img_vc'  => '选中按钮（短）切图',
            'vision_content_json.select_btn_long_img_vc'   => '选中按钮（长）切图',
            'vision_content_json.reward_bottom_box_img_vc' => '奖励底框切图',
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