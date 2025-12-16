<?php

namespace Imee\Controller\Validation\Cs\Statistics\AutoChatLog;

use Imee\Comp\Common\Validation\Validator;
use Imee\Models\Xsst\XsstAutoQuestionLog;

class ListValidation extends Validator
{
    protected function rules()
    {
        return [
            'page' => 'required|integer',
            'limit' => 'required|integer|between:1,1000',
            'sort' => 'string',
            'dir' => 'string|in:asc,desc',
            'start_time' => 'date',
            'end_time' => 'date',
            'type' => 'integer|in:' . implode(',', array_keys(XsstAutoQuestionLog::$displayType)),
            'is_service' => 'integer|in:'. implode(',', array_keys(XsstAutoQuestionLog::$displayIsService)),
            'uid' => 'integer',
            'tag' => 'string',
            'content' => 'string',
            'reply' => 'string',
            'language' => 'string',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'start_time' => '开始时间',
            'end_time' => '结束时间',
            'type' => '类型',
            'is_service' => '是否直接找人工',
            'uid' => '用户ID',
            'app_id' => 'app_id',
            'tag' => '标签',
            'content' => '用户问题',
            'reply' => '用户回复内容',
            'language' => '大区',
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
