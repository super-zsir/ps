<?php

namespace Imee\Controller\Validation\Operate\Honor;

use Imee\Comp\Common\Validation\Validator;

class HonorLevelConfigValidation extends Validator
{
    protected function rules(): array
    {
        return [
            'id'                             => 'integer|min:1',
            'min_level'                      => 'integer|min:1|max:999',
            'max_level'                      => 'integer|min:1|max:999',
            'style_config'                   => 'required|array',
            'style_config.*.level_icon'      => 'required|string',
            'style_config.*.style_icon'      => 'required|string',
            'style_config.*.font_color'      => 'required|array',
            'style_config.*.shade_style'     => 'integer|min:0',
            'style_config.*.shade_direction' => 'integer|min:0',
        ];
    }

    /**
     * 属性
     */
    protected function attributes(): array
    {
        return [
            'id'                             => 'ID',
            'min_level'                      => '荣誉等级区间的最小等级',
            'max_level'                      => '荣誉等级区间的最大等级',
            'style_config'                   => '样式信息',
            'style_config.*.level_icon'      => '等级icon',
            'style_config.*.style_icon'      => '等级样式',
            'style_config.*.font_color'      => '文字颜色',
            'style_config.*.shade_style'     => '渐变样式',
            'style_config.*.shade_direction' => 'shade_direction',
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