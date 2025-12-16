<?php

namespace Imee\Controller\Validation\Operate\Livesticker;

use Imee\Comp\Common\Validation\Validator;

class CustomStickerResourceEditValidation extends Validator
{
    protected function rules()
    {
        return [
            'name'       => 'required|string',
            'img'        => 'required|string',
            'mirror_img' => 'required|string',
            'id'         => 'required|integer',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'name'       => '贴纸名称',
            'img'        => '贴纸图标',
            'mirror_img' => '贴纸图标(镜像)',
            'id'         => 'ID',
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