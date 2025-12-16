<?php

namespace Imee\Controller\Validation\Operate\Giftwall;

use Imee\Comp\Common\Validation\Validator;
use Imee\Service\Operate\Giftwall\GiftWallConfigService;

class GiftWallConfigCreateValidation extends Validator
{
    protected function rules(): array
    {
        $wardType = implode(',', array_column((new GiftWallConfigService())->getAwardTypeMap(), 'value'));
        return [
            'type'                          => 'required|integer',
            'big_area'                      => 'required|array',
            'name_zh_cn'                    => 'required|string',
            'name_en'                       => 'required|string',
            'gift_collect'                  => 'required_if:type,2|array',
            'gift_collect.*.id'             => 'required_if:type,2|integer',
            'gift_collect.*.target_num'     => 'required_if:type,2|integer',
            'gift_collect_3'                => 'required_if:type,3|array',
            'gift_collect_3.*.id_3'         => 'required_if:type,3|integer',
            'gift_collect_3.*.target_num_3' => 'required_if:type,3|integer',
            'collect_day'                   => 'required_if:type,2|integer|min:1',
            'award_list'                    => 'required_if:type,3|array',
            'award_list.*.award_type'       => 'string|in:' . $wardType,
            'award_list.*.cid'              => 'integer|min:1',
            'award_list.*.award_num'        => 'integer',
        ];
    }

    /**
     * 属性
     */
    protected function attributes(): array
    {
        return [
            'big_area'                      => '所属大区',
            'name_zh_cn'                    => '礼物系列名称中文',
            'name_en'                       => '礼物系列名称英文',
            'gift_collect'                  => '礼物配置',
            'gift_collect.*.id'             => '礼物id',
            'gift_collect.*.target_num'     => '礼物数',
            'gift_collect_3'                => '礼物配置',
            'gift_collect_3.*.id_3'         => '礼物ID',
            'gift_collect_3.*.target_num_3' => '礼物数',
            'collect_day'                   => '有效时间',
            'award_list'                    => '礼物奖励',
            'award_list.*.award_type'       => '奖励类型',
            'award_list.*.cid'              => '奖励ID',
            'award_list.*.award_num'        => '数量/有效期day',
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
