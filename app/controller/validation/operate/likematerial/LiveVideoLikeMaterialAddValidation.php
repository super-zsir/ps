<?php

namespace Imee\Controller\Validation\Operate\Likematerial;

use Imee\Comp\Common\Validation\Validator;

class LiveVideoLikeMaterialAddValidation extends Validator
{
    protected function rules(): array
    {
        return [
            'name'           => 'required|string',
            'area_config'    => 'required|array',
            'start_at'       => 'required|date',
            'material'       => 'required|array',
            'material.*.img' => 'required|string',
        ];
    }

    /**
     * 属性
     */
    protected function attributes(): array
    {
        return [
            'id'              => 'ID',
            'name'            => '素材名称',
            'arearea_configa' => '上线大区',
            'start_at'        => '开始时间',
            'material'        => '素材上传',
            'material.*.img'  => 'Image',
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
