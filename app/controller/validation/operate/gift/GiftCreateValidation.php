<?php

namespace Imee\Controller\Validation\Operate\Gift;

use Imee\Comp\Common\Validation\Validator;
use Imee\Models\Xs\XsGift;

class GiftCreateValidation extends Validator
{
    protected function rules()
    {
        return [
            'id'               => 'integer|min:1',
            'tab_id'           => 'integer',
            'name'             => 'string|max:20',
            'name_zh_tw'       => 'string|max:20',
            'name_en'          => 'string|max:20',
            'description'      => 'string|max:500',
            'jump_page'        => 'string|max:200',
            'price'            => 'integer|min:0',
            'price2'           => 'integer|min:0|max:99',
            'display'          => 'required|array',
            'display.*'        => 'in:' . implode(',', array_keys(XsGift::$displays)),
            'sub_display'      => 'required|array',
            'sub_display.*'    => 'in:' . implode(',', array_keys(XsGift::$subDisplays)),
            'type'             => 'required|in:' . implode(',', array_keys(XsGift::$types)),
            'with_end'         => 'required|in:0,1',
            'size'             => 'required|integer|min:0',
            'size_big'         => 'required|integer|min:0',
            'ordering'         => 'required|integer|min:0',
            'tag1'             => 'integer|min:1',
            'tag2'             => 'integer|min:1',
            'title'            => 'required|integer|min:0|max:5',
            'xratio'           => 'integer|min:0|max:100',
            'xtype'            => 'in:' . implode(',', array_keys(XsGift::$xtypes)),
            'excludes'         => 'array',
            'excludes.*'       => 'in:' . implode(',', array_keys(XsGift::$excludes)),
            'tag_url'          => 'required|in:0,1',
            'gift_type'        => 'required|string|in:' . implode(',', array_keys(XsGift::$giftTypes)),
            'income_type'      => 'required|string|in:' . implode(',', array_keys(XsGift::$incomeTypes)),

            //vap
            'vap_type'         => 'string|in:' . implode(',', array_keys(XsGift::$vapTypeMap)),
            'vap_size'         => 'integer|min:0',
            'vap_header'       => 'integer|min:0',
            'vap_header_start' => 'integer|min:0',
            'vap_header_end'   => 'integer|min:0',

            'is_lucky'      => 'required|integer|in:0,1',
            'is_named'      => 'required|integer|in:0,1',
            'is_combo'      => 'required|integer|in:0,1', //是否连击
            'is_skin'       => 'required|integer|in:0,1', //是否皮肤
            'gid_basic'     => 'required_if:is_skin,1|integer|min:0',
            'num_to_unlock' => 'integer|min:0',

            'is_diy'               => 'required|integer|in:0,1', //是否diy
            'diy_type'             => 'required_if:is_diy,1|integer|in:' . implode(',', array_keys(XsGift::$diyTypeMap)),
            'diy_group'            => 'required_if:is_diy,1|integer|min:1',
            'preview_size'         => 'required_if:diy_type,1|integer|min:0',
            'bg_size'              => 'required_if:diy_type,1|integer|min:0',
            'icon_num'             => 'required_if:is_diy,1|in:1,2',
            'unity_sign_direction' => 'required_if:diy_type,2|integer|in:' . implode(',', array_keys(XsGift::$unitySignMap)),
            'unity_android_size'   => 'required_if:diy_type,2|integer|min:0',
            'unity_ios_size'       => 'required_if:diy_type,2|integer|min:0',

            'is_interact'         => 'required_if:tab_id,7|integer|in:0,1', //是否麦位互动礼物
            'is_feed_gift'        => 'required|integer|in:0,1', //是否热门礼物
            'is_relation_gift'    => 'required|integer|in:0,1', //是否关系礼物
            'is_customized'       => 'required_if:tab_id,5|integer|in:0,1', //是否定制礼物
            'customized_gift_uid' => 'required_if:is_customized,1|integer|min:0',
            'relation_gift_num'   => 'required_if:relation_gift_type,1|integer',

            'relation_gift_type' => 'required_if:is_relation_gift,1|integer|in:' . implode(',', array_keys(XsGift::$relationGiftType)),
            'relation_type'      => 'required_if:is_relation_gift,1|integer|in:' . implode(',', array_keys(XsGift::$relationType)),
            'relation_gift_lv'   => 'required_if:is_relation_gift,1|integer|in:' . implode(',', array_keys(XsGift::$relationLv)),

            'is_privilege'   => 'required|integer|in:0,1',
            'privilege_type' => 'required_if:is_privilege,1|integer',
            'family_lv'      => 'required_if:is_privilege,1|integer',

            'is_blind_box'    => 'required|integer|in:0,1',
            'gifts'           => 'required_if:is_blind_box,1|array',
            'super_gift_id'   => 'required_if:is_blind_box,1',
            'jackpot_gift_id' => 'required_if:is_blind_box,1',
            'is_secret_gift'  => 'required|integer|in:0,1',
        ];
    }

    /**
     * 属性
     */
    protected function attributes()
    {
        return [
            'gift_type'        => '礼物类型',
            'income_type'      => '礼物收入类型',
            'ordering'         => '排序',
            'vap_header_start' => '头像出现帧',
            'vap_header_end'   => '头像消失帧',
            'name'             => '礼物名称',
            'description'      => '礼物描述',
            'jump_page'        => '跳转url',
            'price'            => 'price(整数位)',
            'price2'           => 'price(小数位)',
            'num_to_unlock'    => '礼物皮肤解锁限制',
            'is_diy'           => '是否diy礼物',
            'diy_type'         => 'diy礼物类型',
            'diy_group'        => '礼物分组',
            'preview_size'     => '礼物预览图大小',
            'bg_size'          => '礼物背景大小',
            'icon_num'         => '支持头像个数',
            'is_skin'          => '是否礼物皮肤',
            'gid_basic'        => '关联礼物id',
            'is_privilege'     => '是否特权礼物',
            'privilege_type'   => '特权类型',
            'family_lv'        => '家族等级',
            'is_blind_box'     => '是否盲盒礼物',
            'gifts'            => '盲盒礼物配置',
            'super_gift_id'    => '超级稀缺礼物id',
            'jackpot_gift_id'  => 'jackpot礼物id',
            'is_secret_gift'   => '是否私密礼物',
        ];
    }

    /**
     * 提示信息
     */
    protected function messages()
    {
        return [
            'title.max'                      => 'title 最小值0，最大值5',
            'title.min'                      => 'title 最小值0，最大值5',
            'xratio.min'                     => 'xratio 最小值0，最大值100',
            'vap_header_start.min'           => '头像出现帧 最小值0',
            'vap_header_end.min'             => '头像消失帧 最小值0',
            'relation_gift_num.required_if'  => '请填写 收到奖励需要的礼物数量',
            'relation_gift_type.required_if' => '请填写 礼物属性',
            'relation_type.required_if'      => '请填写 关系属性',
            'relation_gift_lv.required_if'   => '请填写 关系等级',
            'is_interact.required_if'        => 'tab为Interact时，是否麦位互动礼物必须选为 是',
            'privilege_type.required_if'     => '请填写 特权类型',
            'family_lv.required_if'          => '请填写 家族等级',
            'gifts.required_if'              => '请填写 盲盒礼物配置',
            'super_gift_id.required_if'      => '请填写 超级稀缺礼物id',

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
                'code'    => 0,
                'msg'     => '',
                'total'   => 1,
                'data'    => [
                ],
            ],
        ];
    }
}