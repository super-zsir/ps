<?php

namespace Imee\Controller\Validation\Operate\Pretty\Commodity;

use Imee\Comp\Common\Validation\Validator;

class ExportValidation extends Validator
{
    protected function rules()
    {
        return [
            'id' => 'integer',
            'pretty_uid' => 'string',
            'support_area' => 'string',
            'on_sale_status' => 'integer'
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'id' => 'ID',
            'pretty_uid' => '靓号ID',
            'support_area' => '大区',
            'on_sale_status' => '上架状态',
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
