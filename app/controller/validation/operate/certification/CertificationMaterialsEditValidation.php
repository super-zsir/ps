<?php

namespace Imee\Controller\Validation\Operate\Certification;

use Imee\Comp\Common\Validation\Validator;

class CertificationMaterialsEditValidation extends Validator
{
    protected function rules(): array
    {
        return [
            'id'                =>  'required|integer',
            'name'              =>  'required|string',
            'icon'              =>  'required|string',
            'label'             =>  'required|string',
            'font_color'        =>  'required|string',
            'default_content'   =>  'required|string|max:30',
        ];
    }

    /**
     * 属性
     */
    protected function attributes(): array
    {
        return [
            'id'                =>  'ID',
            'name'              =>  'Material Name',
            'icon'              =>  'Material Icon',
            'label'             =>  'Material Label',
            'font_color'        =>  'Font Color',
            'default_content'   =>  'Default Content',
        ];
    }

    /**
     * 提示信息
     */
    protected function messages(): array
    {
        return [
            'default_content.max' => '{attr} 最大长度为30'
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