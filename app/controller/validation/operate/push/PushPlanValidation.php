<?php

namespace Imee\Controller\Validation\Operate\Push;

use Imee\Comp\Common\Validation\Validator;

class PushPlanValidation extends Validator
{
    protected function rules()
    {
        return [
            'name'           => 'required|string',
            'content_id'     => 'required|array',
            'content_repeat' => 'required|integer',
            'mode'           => 'required|integer',
            'filter_type'    => 'required|integer'
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'name'           => '计划名称',
            'content_id'     => '关联文案ID',
            'content_repeat' => '文案是否排重',
            'mode'           => '推送方式',
            'filter_type'    => '推送类型'
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
                'code' => 0,
                'msg' => '',
                'data' => null,
            ],
        ];
    }
}