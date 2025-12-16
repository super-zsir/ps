<?php

namespace Imee\Controller\Validation\Operate\Livesticker;

use Imee\Comp\Common\Validation\Validator;

class StickerResourceAddValidation extends Validator
{
    protected function rules()
    {
        return [
            'name'     => 'required|string',
            'img'      => 'required|string',
            'resource' => 'required|string',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'name'     => '贴纸名称',
            'img'      => '贴纸图标',
            'resource' => '贴纸资源',
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