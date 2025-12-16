<?php

namespace Imee\Controller\Validation\Operate\Viprecord;

use Imee\Comp\Common\Validation\Validator;
use Imee\Models\Xs\XsVipRecord;

class ListValidation extends Validator
{
    protected function rules()
    {
        return [
            'vip_level' => 'integer',
            'record_type' => 'integer|in:' . implode(',', array_keys(XsVipRecord::$displayRecordType)),
            'user_big_area_id' => 'integer',
            'uids' => 'string',
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
            'vip_level' => 'VIP等级',
            'record_type' => '变更类型',
            'user_big_area_id' => '用户大区',
            'uids' => '多个uid',
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