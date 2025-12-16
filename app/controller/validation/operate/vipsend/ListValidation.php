<?php

namespace Imee\Controller\Validation\Operate\Vipsend;

use Imee\Comp\Common\Validation\Validator;
use Imee\Models\Xsst\BmsVipSend;

class ListValidation extends Validator
{
    protected function rules()
    {
        return [
            'op_uid' => 'integer',
            'state' => 'integer|in:' . implode(',', array_keys(BmsVipSend::$displayState)),
            
            'id' => 'integer',
            'dateline_sdate' => 'date',
            'dateline_edate' => 'date',
            'page' => 'required|integer',
            'limit' => 'required|integer|between:1,1000',
            'sort' => 'string',
            'dir' => 'string|in:asc,desc',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'op_uid' => '创建人ID',
            'state' => '状态',
            'dateline_sdate' => '起始时间',
            'dateline_edate' => '结束时间',
        ];
    }

    /**
     * 提示信息
     */
    protected function messages()
    {
        return [

        ];
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