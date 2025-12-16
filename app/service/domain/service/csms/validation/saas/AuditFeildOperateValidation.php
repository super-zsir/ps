<?php

namespace Imee\Service\Domain\Service\Csms\Validation\Saas;

use Imee\Comp\Common\Validation\Validator;

class AuditFeildOperateValidation extends Validator
{
    protected function rules()
    {
        return [
            'cid' => 'required|int',
//            'db_name' => 'required|string',
//            'table_name' => 'required|string',
            'field' => 'required|string',
            'type' => 'required|string',
            'sort' => 'required|int',
//            'pk_field' => 'required|string',
//            'uid_field' => 'required|string',
//            'joinup' => 'required|in:binlog,nsq,kafka,api,rpc',
//            'ignore_write' => 'required|int',
//            'ignore_update' => 'required|int',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'choice' => '审核项标识',
            'choice_name' => '审核项名称',
            'type' => '审核内容类型',
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
