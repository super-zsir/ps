<?php

namespace Imee\Controller\Validation\Operate\Livesticker;

use Imee\Comp\Common\Validation\Validator;

class StickerListValidation extends Validator
{
    protected function rules()
    {
        return [
            'big_area_id' => 'required',
            'sn'          => 'required|integer',
            'sticker_id'  => 'required|integer',
            'status'      => 'required|integer'
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'big_area_id' => '生效大区',
            'sn'          => '序号',
            'sticker_id'  => '贴纸ID',
            'status'      => '生效状态',
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