<?php

namespace Imee\Controller\Validation\Operate\Face;

use Imee\Comp\Common\Validation\Validator;

class UserFaceValidation extends Validator
{
    protected function rules()
    {
        return [
            'uid'     => 'required|integer',
            'type'    => 'required|integer',
            'image'   => 'required_if:type,1|string',
            'new_uid' => 'required_if:type,2|integer',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'uid'     => '用户UID',
            'type'    => '操作类型',
            'image'   => '人脸图片',
            'new_uid' => '新用户UID',
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