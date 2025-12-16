<?php

namespace Imee\Controller\Validation\Operate\Emoticons\Emoticons;

use Imee\Comp\Common\Validation\Validator;

class EmoticonsAddValidation extends Validator
{
    protected function rules()
    {
        return [
            'group_id'          => 'required|integer',
            'bigarea_id'        => 'required_if:identity,2,3,5|integer',
            'bigarea_ids'       => 'required_if:identity,1,4,7|array',
            'identity'          => 'required|integer',
            'emoticons_meta_id' => 'required|integer',
            'price'             => 'required_if:identity,5|integer|min:0',
            'durtion'           => 'required_if:identity,5|integer|min:0',
            'family_lv'         => 'required_if:identity,7|integer',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'id'                => 'ID',
            'group_id'          => '所属标签',
            'bigarea_id'        => '所属大区',
            'bigarea_ids'       => '所属大区',
            'bigarea'           => '所属大区',
            'identity'          => '可用人群',
            'emoticons_meta_id' => '表情包ID',
            'price'             => '钻石数',
            'durtion'           => '持有天数',
            'family_lv'         => '家族等级',
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
                'total'   => 1,
                'data'    => [
                ],
            ],
        ];
    }
}