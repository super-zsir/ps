<?php

namespace Imee\Controller\Validation\Operate\Game;

use Imee\Comp\Common\Validation\Validator;

class GameWebValidation extends Validator
{
    protected function rules(): array
    {
        return [
            'poolID'          => 'required|integer',
            'areasID'         => 'required|array',
            'injectRate'      => 'required',
            'resetType'       => 'required|integer',
        ];
    }

    /**
     * 属性
     */
    protected function attributes(): array
    {
        return [
            'poolID'          => 'ID',
            'areasID'         => '运营大区',
            'injectRate'      => '最大产出比例',
            'resetType'       => '重置方式',
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
