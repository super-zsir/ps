<?php

namespace Imee\Controller\Validation\Operate\Welcomegiftbag;

use Imee\Comp\Common\Validation\Validator;
use Imee\Models\Xs\XsGiftBag;

class CreateByCrowdValidation extends Validator
{
    protected function rules()
    {
        return [
            'gb_id'          => 'required|integer',
            'num'            => 'required|integer|min:1',
            'valid_day'      => 'required|integer|min:1',
            'send_user_type' => 'required|integer|in:3,4',
            'bid'            => 'string',
            'bid_file'       => 'string',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'gb_id'          => '礼包ID',
            'num'            => '礼包数量',
            'valid_day'      => '有效天数',
            'send_user_type' => '发放用户',
            'bid'            => '公会id',
            'bid_file'       => '上传公会id',
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