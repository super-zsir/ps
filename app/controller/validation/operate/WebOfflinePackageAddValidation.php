<?php

namespace Imee\Controller\Validation\Operate;

use Imee\Comp\Common\Validation\Validator;
use Imee\Models\Xs\XsWebPageResource;

class WebOfflinePackageAddValidation extends Validator
{
    protected function rules()
    {
        return [
            'resource_id'  => 'required|string',
            'name'         => 'required|string',
            'resource_url' => 'required|string',
            'status'       => 'required|integer|in:' . implode(',', array_keys(XsWebPageResource::$statusMap)),
            'force_update' => 'required|integer|in:' . implode(',', array_keys(XsWebPageResource::$statusMap)),
            'remark'       => 'string',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'id'           => '序号',
            'resource_id'  => 'ID',
            'name'         => '网页名称',
            'resource_url' => '文件上传',
            'status'       => '是否生效',
            'force_update' => '是否强制更新',
            'remark'       => '备注',
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