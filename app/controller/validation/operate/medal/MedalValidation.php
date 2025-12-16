<?php

namespace Imee\Controller\Validation\Operate\Medal;

use Imee\Comp\Common\Validation\Validator;

class MedalValidation extends Validator
{
    protected function rules()
    {
        return [
            'type'              => 'required|integer',
            'big_area'          => 'string',
            'jump_url'          => 'string',
            'cn_name'           => 'required|string',
//            'cn_description'    => 'required|string',
            'en_name'           => 'required|string',
//            'en_description'    => 'required|string',
            'image_1'           => 'required|string',
            'image_2'           => 'required|string',
            'image_3'           => 'required|string'
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'type'  => '勋章类型',
            'big_area'  => '运营大区',
            'jump_url'  => '跳转链接',
            'cn_name'  => '中文名称',
//            'cn_description'  => '中文描述',
            'en_name'  => '英文名称',
//            'en_description'  => '英文描述',
            'image_1'  => '未激活图片',
            'image_2'  => '已激活图片',
            'image_3'  => '动态图片',
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