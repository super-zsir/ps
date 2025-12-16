<?php

namespace Imee\Controller\Validation\Operate\Activity\Firstcharge;

use Imee\Comp\Common\Validation\Validator;

class FirstChargeActivityConfigModifyValidation extends Validator
{
    protected function rules()
    {
        return [
            'id'                                                => 'required|integer',
            'language'                                          => 'required|string',
            'recharge_channels'                                 => 'required|array',
            'comment'                                           => 'string',
            'level_award_list'                                  => 'required|array',
            'level_award_list.*.reward_level'                   => 'required|integer',
            'level_award_list.*.level'                          => 'required|integer|min:1|max:2147483647',
            'level_award_list.*.award_list'                     => 'required|array',
            'vision_content_json'                               => 'required|array',
            'vision_content_json.title_text_color_vc'           => 'required|string',
            'vision_content_json.module_bgc_color_vc'           => 'required|string',
            'vision_content_json.bgc_vc'                        => 'required|string',
            'vision_content_json.reward_bottom_color_vc'        => 'required|string',
            'vision_content_json.special_text_color_vc'         => 'required|string',
            'vision_content_json.main_text_color_vc'            => 'required|string',
            'vision_content_json.corner_marker_bottom_color_vc' => 'required|string',
            'vision_content_json.corner_marker_text_color_vc'   => 'required|string',
            'vision_content_json.recharge_text_color_vc'        => 'required|string',
            'vision_content_json.head_img_vc'                   => 'required|string',
            'vision_content_json.title_img_vc'                  => 'required|string',
            'vision_content_json.module_bgx_vc'                 => 'required|string',
            'vision_content_json.recharge_button_vc'            => 'required|string',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'id'                                                => 'ID',
            'language'                                          => '活动语言',
            'recharge_channels'                                 => '充值渠道',
            'comment'                                           => '活动说明',
            'level_award_list'                                  => '档位及奖励配置',
            'level_award_list.*.reward_level'                   => '档位index',
            'level_award_list.*.level'                          => '档位',
            'level_award_list.*.award_list'                     => '奖励配置',
            'vision_content_json'                               => '视觉配置',
            'vision_content_json.title_text_color_vc'           => '标题字色',
            'vision_content_json.module_bgc_color_vc'           => '模块背景色',
            'vision_content_json.bgc_vc'                        => '背景色',
            'vision_content_json.reward_bottom_color_vc'        => '奖励底色',
            'vision_content_json.special_text_color_vc'         => '特殊字色',
            'vision_content_json.main_text_color_vc'            => '主字色',
            'vision_content_json.corner_marker_bottom_color_vc' => '角标底色',
            'vision_content_json.corner_marker_text_color_vc'   => '角标字色',
            'vision_content_json.recharge_text_color_vc'        => '充值字色',
            'vision_content_json.head_img_vc'                   => '头图',
            'vision_content_json.title_img_vc'                  => '标题',
            'vision_content_json.module_bgx_vc'                 => '模块背景',
            'vision_content_json.recharge_button_vc'            => '充值按钮',
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