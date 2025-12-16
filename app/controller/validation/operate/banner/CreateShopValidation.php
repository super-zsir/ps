<?php

namespace Imee\Controller\Validation\Operate\Banner;

use Imee\Comp\Common\Validation\Validator;

class CreateShopValidation extends Validator
{
    protected function rules()
    {
        return [
            'type'             => 'required|string',
            'stype'            => 'required|string',
            'position'         => 'required|string',
            'title'            => 'string',
            'url'              => 'required|string|min:1',
            'share_title'      => 'string',
            'share_desc'       => 'string',
            'share_url'        => 'url',
            'share_icon'       => 'string',
            'role'             => 'required_if:position,live,videofeed|integer|min:0',
            'deleted'          => 'required|integer|in:0,1',
            'begin_time'       => 'date',
            'end_time'         => 'date',
            'duration'         => 'integer|min:0|max:100',
            'ordering'         => 'integer|min:0|max:1000',
            'area'             => 'required|string',
            'language'         => 'string',
            'allowed_users'    => 'string',
            'limit_lv'         => 'integer',
            'display_position' => 'integer',
            'note'             => 'required|string',
            'icon'             => 'required|string',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'position'         => 'banner类型',
            'type'             => '类型',
            'stype'            => '展现平台',
            'title'            => '标题',
            'icon'             => 'banner图',
            'url'              => '链接',
            'share_title'      => '分享标题',
            'share_desc'       => '分享描述',
            'share_url'        => '分享链接',
            'share_icon'       => '分享icon',
            'role'             => '可用人群',
            'begin_time'       => '开始时间',
            'end_time'         => '结束时间',
            'duration'         => '显示时长',
            'language'         => '语言',
            'area'             => '大区',
            'ordering'         => '排序',
            'deleted'          => '禁用状态',
            'allowed_users'    => '用户UID',
            'limit_lv'         => '用户等级',
            'display_position' => '展示位置',
            'note'             => '备注',
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
            ],
        ];
    }
}