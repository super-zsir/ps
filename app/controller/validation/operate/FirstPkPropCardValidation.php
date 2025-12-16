<?php

namespace Imee\Controller\Validation\Operate;

use Imee\Comp\Common\Validation\Validator;

class FirstPkPropCardValidation extends Validator
{
    protected function rules()
    {
        return [
            'bigarea_id'            => 'required|integer',
            'diamond'                => 'required|integer|min:1',
            'status'                 => 'required|integer|min:0',
            'config_list'            => 'required|array|between:1,10',
            'config_list.*.type'     => 'required',
            'config_list.*.id'       => 'integer|min:0',
            'config_list.*.weight'   => 'integer|min:1',
            'config_list.*.validity' => 'integer|min:0',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'bigarea_id'       => '大区',
            'diamond'           => '首次送礼需达标钻石数',
            'status'            => '状态',
            'config'            => '奖励配置',
            'config.*.type'     => '奖励类型',
            'config.*.id'       => '物品id',
            'config.*.weight'   => '权重',
            'config.*.validity' => '有效天数',
        ];
    }

    /**
     * 提示信息
     */
    protected function messages()
    {
        return [
            'config_list.between'  => '奖励配置最少1个最多10个',
            'config_list.required' => '奖励配置最少1个最多10个',
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