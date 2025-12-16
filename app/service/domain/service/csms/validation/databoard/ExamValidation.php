<?php

namespace Imee\Service\Domain\Service\Csms\Validation\Databoard;

use Imee\Comp\Common\Validation\Validator;

class ExamValidation extends Validator
{
    protected function rules()
    {
        return [
            'id' => 'numeric|min:0',
            'total_score' => 'required|numeric|min:0',
            'arm' => 'required|numeric|min:0',
            'exam_aless' => 'required|numeric|min:0',
            'exam_bless' => 'required|numeric|min:0',
            'exam_cless' => 'required|numeric|min:0',
            'exam_good_score' => 'required|numeric|min:0',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'module' => '模块',
            'choice' => '审核项',
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
                'total' => 1,
                'data' => [
                ],
            ],
        ];
    }
}
