<?php

namespace Imee\Controller\Validation\Operate\Activity\Honorwalltemplate;

use Imee\Comp\Common\Validation\Validator;
use Imee\Models\Xs\XsActHonourWallConfig;

class AddValidation extends Validator
{
    protected function rules(): array
    {
        return [
            'title'                                                 => 'required|string',
            'language'                                              => 'required|string',
            'vision_content_json'                                   => 'required|array',
            'vision_content_json.background_color'                  => 'required|string',
            'vision_content_json.all_border_color'                  => 'required|string',
            'vision_content_json.button_select_text_color'          => 'required|string',
            'vision_content_json.button_no_select_text_color'       => 'required|string',
            'vision_content_json.nickname_id_text_color'            => 'required|string',
            'vision_content_json.master_text_color'                 => 'required|string',
            'vision_content_json.pop_bottom_border_color'           => 'required|string',
            'vision_content_json.head_image'                        => 'required|string',
            'vision_content_json.select_button_image'               => 'required|string',
            'vision_content_json.title_border_image'                => 'required|string',
            'vision_content_json.no_select_button_image'            => 'required|string',
            'vision_content_json.top3_user_background_image'        => 'string',
            'vision_content_json.one_user_head_image'               => 'string',
            'vision_content_json.two_user_head_image'               => 'string',
            'vision_content_json.three_user_head_image'             => 'string',
            'vision_content_json.top3_background_image'             => 'string',
            'vision_content_json.one_head_image'                    => 'string',
            'vision_content_json.two_head_image'                    => 'string',
            'vision_content_json.three_head_image'                  => 'string',
            'vision_content_json.top3_family_background_image'      => 'string',
            'vision_content_json.one_family_head_image'             => 'string',
            'vision_content_json.two_family_head_image'             => 'string',
            'vision_content_json.three_family_head_image'           => 'string',
            'vision_content_json.top3_cp_background_image'          => 'string',
            'vision_content_json.one_cp_head_image'                 => 'string',
            'vision_content_json.two_cp_head_image'                 => 'string',
            'vision_content_json.three_cp_head_image'               => 'string',
            'vision_content_json.top3_custom_gift_background_image' => 'string',
            'vision_content_json.one_custom_gift_head_image'        => 'string',
            'vision_content_json.two_custom_gift_head_image'        => 'string',
            'vision_content_json.three_custom_gift_head_image'      => 'string',
            'vision_content_json.country_pk_bottom_border_image'    => 'string',
            'rule_content'                                          => 'string',
            'is_show'                                               => 'required|integer|in:' . implode(',', array_keys(XsActHonourWallConfig::$isShowMap)),
            'act_honour_wall_tab'                                   => 'required|array',
            'act_honour_wall_tab.*.name'                            => 'string',
            'act_honour_wall_tab.*.data_source_list'                => 'required|array'
        ];
    }

    /**
     * 属性
     */
    protected function attributes(): array
    {
        return [

        ];
    }

    /**
     * 提示信息
     */
    protected function messages(): array
    {
        return [
            'title'                                                 => '荣誉墙标题',
            'language'                                              => '语言',
            'vision_content_json'                                   => '视觉区域',
            'vision_content_json.background_color'                  => '背景色',
            'vision_content_json.all_border_color'                  => '所有边框色',
            'vision_content_json.button_select_text_color'          => '按钮选中字色',
            'vision_content_json.button_no_select_text_color'       => '按钮未选中字色',
            'vision_content_json.nickname_id_text_color'            => '昵称/ID字色',
            'vision_content_json.master_text_color'                 => '主字色',
            'vision_content_json.pop_bottom_border_color'           => '弹窗底框色',
            'vision_content_json.head_image'                        => '头图',
            'vision_content_json.select_button_image'               => '选中按钮',
            'vision_content_json.title_border_image'                => '标题框',
            'vision_content_json.no_select_button_image'            => '未选中按钮',
            'vision_content_json.top3_user_background_image'        => '前三名用户背景图',
            'vision_content_json.one_user_head_image'               => '第一名用户头像框',
            'vision_content_json.two_user_head_image'               => '第二名用户头像框',
            'vision_content_json.three_user_head_image'             => '第三名用户头像框',
            'vision_content_json.top3_background_image'             => '前三名背景图',
            'vision_content_json.one_head_image'                    => '第一名头像框',
            'vision_content_json.two_head_image'                    => '第二名头像框',
            'vision_content_json.three_head_image'                  => '第三名头像框',
            'vision_content_json.top3_family_background_image'      => '前三名家族背景图',
            'vision_content_json.one_family_head_image'             => '第一名家族头像框',
            'vision_content_json.two_family_head_image'             => '第二名家族头像框',
            'vision_content_json.three_family_head_image'           => '第三名家族头像框',
            'vision_content_json.top3_cp_background_image'          => '前三名CP背景图',
            'vision_content_json.one_cp_head_image'                 => '第一名CP头像框',
            'vision_content_json.two_cp_head_image'                 => '第二名CP头像框',
            'vision_content_json.three_cp_head_image'               => '第三名CP头像框',
            'vision_content_json.top3_custom_gift_background_image' => '前三名定制礼物背景图',
            'vision_content_json.one_custom_gift_head_image'        => '第一名定制礼物头像框',
            'vision_content_json.two_custom_gift_head_image'        => '第二名定制礼物头像框',
            'vision_content_json.three_custom_gift_head_image'      => '第三名定制礼物头像框',
            'vision_content_json.country_pk_bottom_border_image'    => '国家Pk底框',
            'rule_content'                                          => '规则说明',
            'is_show'                                               => '展示状态',
            'act_honour_wall_tab'                                   => '荣誉墙配置',
            'act_honour_wall_tab.*.name'                            => 'tab名称',
            'act_honour_wall_tab.*.data_source_list'                => '数据来源',
        ];
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

