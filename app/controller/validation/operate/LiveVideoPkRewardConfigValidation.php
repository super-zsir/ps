<?php

namespace Imee\Controller\Validation\Operate;

use Imee\Comp\Common\Validation\Validator;

class LiveVideoPkRewardConfigValidation extends Validator
{
    protected function rules()
    {
        return [
            'big_area_id'                    => 'required|integer',
            'top_n'                          => 'required|integer|min:1|max:20',
            'config'                         => 'required|array|between:1,20',
            'config.*.task_value'            => 'required|integer|min:1',
            'config.*.config_list'           => 'required|array|between:1,10',
            'config.*.config_list.*.type'    => 'required',
            'config.*.config_list.*.id'      => 'required|integer',
            'config.*.config_list.*.weight'  => 'required|integer|min:1',
            'config.*.config_list.*.num'     => 'required_if:config.*.config_list.*.type,3|integer|min:1',
            'config.*.config_list.*.num_day' => 'required_if:config.*.config_list.*.type,20|integer|min:1',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'big_area_id'                    => '大区',
            'config.*.task_value'            => 'pk任务值',
            'top_n'                          => '前N名用户得到奖励',
            'config.*.config_list'           => '奖励配置',
            'config.*.config_list.*.type'    => '奖励类型',
            'config.*.config_list.*.id'      => '物品id',
            'config.*.config_list.*.weight'  => '获得机会',
            'config.*.config_list.*.num'     => '有效天数',
            'config.*.config_list.*.num_day' => '有效期（h）',
        ];
    }

    /**
     * 提示信息
     */
    protected function messages()
    {
        return [
            'config.between'  => '奖励配置最少1个最多20个',
            'config.required' => '奖励配置最少1个最多20个',
        ];
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