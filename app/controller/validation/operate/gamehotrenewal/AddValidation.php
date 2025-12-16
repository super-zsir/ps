<?php

namespace Imee\Controller\Validation\Operate\Gamehotrenewal;

use Imee\Comp\Common\Validation\Validator;

class AddValidation extends Validator
{
    protected function rules(): array
    {
        return [
            'app_id'      => 'required|integer',
            'game_name'   => 'required|string',
            'source_path' => 'required|string',
            'orientation' => 'required|string',
            'version'     => 'required|string',
            'remark'      => 'required|string',
        ];
    }

    /**
     * 属性
     */
    protected function attributes(): array
    {
        return [
            'app_id'      => 'app_id',
            'game_name'   => '游戏名称',
            'source_path' => '资源包路径',
            'orientation' => '方向',
            'version'     => '版本号',
            'remark'      => '备注'
        ];
    }

    /**
     * 提示信息
     */
    protected function messages(): array
    {
        return [];
    }

    /**
     * 返回数据结构
     */
    protected function response(): array
    {
        return [
            'result' => [
                'success' => true,
                'code'    => 0,
                'msg'     => '',
                'data'    => true,
            ],
        ];
    }
}
