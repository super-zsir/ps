<?php

namespace Imee\Controller\Validation\Operate;

use Imee\Comp\Common\Validation\Validator;
use Imee\Service\Operate\VideoConfigFileManageService;

class VideoConfigFileManageAddValidation extends Validator
{
    protected function rules()
    {
        return [
            'key'         => 'required|string',
            'name'        => 'required|string',
            'config_type' => 'required|integer|in:' . implode(',', array_keys(VideoConfigFileManageService::$configTypeMap)),
            'file'        => 'required_if:type,' . VideoConfigFileManageService::CONFIG_TYPE_URL . '|string',
            'content'     => 'required_if:type,' . VideoConfigFileManageService::CONFIG_TYPE_JSON . '|string',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'key'         => 'Key',
            'name'        => '文件名称',
            'config_type' => '文件类型',
            'file'        => '文件上传',
            'content'     => '文件内容',
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