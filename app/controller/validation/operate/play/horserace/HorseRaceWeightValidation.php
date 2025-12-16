<?php

namespace Imee\Controller\Validation\Operate\Play\Horserace;

use Imee\Comp\Common\Validation\Validator;
use Imee\Models\Xs\XsBigarea;

class HorseRaceWeightValidation extends Validator
{
    protected function rules()
    {
        return [
            'horse_race_engine_id'    => 'required|integer|in:' . implode(',', [XsBigarea::HORSE_RACE_A, XsBigarea::HORSE_RACE_B]),
            'horse_config'            => 'required|array',
            'horse_config.*.id'       => 'required|integer|min:0',
            'horse_config.*.hit_rate' => 'required|integer|min:0',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'horse_race_engine_id'    => '奖池类型',
            'horse_config'            => '配置',
            'horse_config.*.id'       => '配置项',
            'horse_config.*.hit_rate' => '权重值',
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
                'data'    => null,
            ],
        ];
    }
}