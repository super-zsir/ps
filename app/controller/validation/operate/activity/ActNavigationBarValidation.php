<?php

namespace Imee\Controller\Validation\Operate\Activity;

use Imee\Comp\Common\Validation\Validator;

class ActNavigationBarValidation extends Validator
{
    protected function rules(): array
    {
        return [
            'id'                                    => 'integer',
            'title'                                 => 'required|string',
            'language'                              => 'required|string',
            'vision_content_json'                   => 'required|array',
            'main_race_json'                        => 'required|array',
            'attach_race_json'                      => 'array',
            'vision_content_json.button_img_return' => 'string',
            'vision_content_json.button_img_share'  => 'string',
            'vision_content_json.color_navigation' => 'required|string',
            'vision_content_json.color_attach'     => 'required|string',
            'vision_content_json.color_wait_start'  => 'required|string',
            'main_race_json.*.name'                 => 'required|string',
            'main_race_json.*.start_icon'           => 'required|string',
            'main_race_json.*.end_icon'             => 'required|string',
            'main_race_json.*.act_type'             => 'required|integer|min:1',
            'main_race_json.*.act_id'               => 'required|integer|min:1',
            'attach_race_json.*.name'               => 'string',
            'attach_race_json.*.icon'               => 'string',
            'attach_race_json.*.act_type'           => 'integer|min:1',
            'attach_race_json.*.act_id'             => 'integer|min:1',
        ];
    }

    /**
     * 属性
     */
    protected function attributes(): array
    {
        return [
            'title'                                 => '页面语言',
            'language'                              => '页面名称',
            'vision_content_json'                   => '视觉配置',
            'main_race_json'                        => '主赛程区域',
            'attach_race_json'                      => '附加玩法区域',
            'vision_content_json.button_img_return' => '返回按钮',
            'vision_content_json.button_img_share'  => '分享按钮',
            'vision_content_json.color_navigation'  => '导航栏字色',
            'vision_content_json.color_attach'      => '附加玩法字色',
            'vision_content_json.color_wait_start'  => '导航栏未开始字色',
            'main_race_json.*.name'                 => '主赛程区域-活动标题',
            'main_race_json.*.start_icon'           => '主赛程区域-赛程已开始icon',
            'main_race_json.*.end_icon'             => '主赛程区域-赛程未开始icon',
            'main_race_json.*.act_type'             => '主赛程区域-模版类型',
            'main_race_json.*.act_id'               => '主赛程区域-活动ID',
            'attach_race_json.*.name'               => '附加玩法区域-活动标题',
            'attach_race_json.*.icon'               => '附加玩法区域-icon',
            'attach_race_json.*.act_type'           => '附加玩法区域-模版类型',
            'attach_race_json.*.act_id'             => '附加玩法区域-活动ID',
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