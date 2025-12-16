<?php

namespace Imee\Controller\Validation\Operate\Livevideo;

use Imee\Comp\Common\Validation\Validator;
use Imee\Models\Xs\XsVideoLiveSessionLog;

class LiveVideoListStopValidate extends Validator
{
    protected function rules()
    {
        return [
            'rid'     => 'required|integer|min:1',
            'uid'     => 'required|integer',
            'reason'  => 'required|integer|in:' . implode(',', array_keys(XsVideoLiveSessionLog::$reasonMap)),
            'remarks' => 'string',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'rid'     => 'rid',
            'uid'     => 'uid',
            'reason'  => '原因',
            'remarks' => '备注',
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