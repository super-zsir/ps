<?php

namespace Imee\Controller\Validation\Operate\Commodity;

use Imee\Comp\Common\Validation\Validator;
use Imee\Models\Xs\XsCommodityAdmin;

class AddValidation extends Validator
{
    protected function rules(): array
    {
        $data = [
            'group_id'          => 'integer',
            'name'              => 'string',
            'name_en'           => 'required|string',
            'type'              => 'required|string',
            'image'             => 'string',
            'sub_type'          => 'string',//子类型
            'price'             => 'integer|min:0',
            'money_type'        => 'required|string',//财富类型
            'can_opened_by_box' => 'required|integer',//是否礼盒开出
            'description'       => 'string',
            'description_zh_tw' => 'string',
            'description_en'    => 'string',
            'image_bg'          => 'string',//背景图
            'show_on_panel'     => 'integer',//是否在礼物面板展示
            'title'             => 'integer',//爵位等级
            'poolid'            => 'integer',//奖池
            'weight'            => 'integer',//权重
            'tag_ids'           => 'string',//标签id
            'panel_image'       => 'string',
            'color'             => 'string',//颜色#FFFFFF
            'limit_start'       => 'integer',//购买限制指定范围
            'limit_end'         => 'integer',
            'grant_limit'       => 'string',//购买限制
            'grant_way'         => 'string',//获取方式
            'name_series'       => 'string',//系列名称
            'startline'         => 'string',//开始使用时间
            'endline'           => 'string',//结束使用时间
            'duction_rate'      => 'integer|min:0|max:100',//折扣率(80表示8折)
            'duction_limit_min' => 'integer',//折扣起售数量
            'duction_limit_max' => 'integer',
            'mark'              => 'string',//备注
            'saling_on_shop'    => 'integer',//商店出售
            'can_give'          => 'integer',//物品是否能够赠送 1代表可以 0代表不可以 默认可以赠送
            'is_continue'       => 'integer',//是否续费才能用
            'jump_page'         => 'string',//跳转页面
            'ext_id'            => 'integer',//优惠券物品id
            'ext_name'          => 'string',//优惠券名称
            'coupon_type'       => 'string',//优惠券类型
            'duction_money'     => 'integer',//优惠券金额分
            'only_newpay'       => 'integer',//是否新支付
            'period'            => 'integer',//有效天数
            'period_hour'       => 'integer',//有效时长
            'money'             => 'integer',//收集金额总额
            'game_type'         => 'string',//门票类型
        ];

        foreach (XsCommodityAdmin::$nameBigarea as $key => $_) {
            if (!empty($data[$key])) {
                continue;
            }
            $data[$key] = 'string';
        }

        foreach (XsCommodityAdmin::$imageBigarea as $key => $_) {
            if (!empty($data[$key])) {
                continue;
            }
            $data[$key] = 'string';
        }

        return $data;
    }

    /**
     * 属性
     */
    protected function attributes(): array
    {
        return [
            'group_id'          => '分组ID',
            'name'              => '物品中文名称',
            'name_en'           => '物品英文名称',
            'type'              => '物品类型',
            'image'             => '物品图片',
            'sub_type'          => '物品子类型',
            'price'             => '物品价格',
            'show_on_panel'     => '是否在礼物面板展示',
            'title'             => '爵位等级',
            'poolid'            => '奖池',
            'weight'            => '权重',
            'tag_ids'           => '标签',
            'color'             => '颜色',
            'grant_limit_level' => 'ka等级',
            'limit_start'       => '购买限制指定开始范围',
            'limit_end'         => '购买限制指定结束范围',
            'grant_limit'       => '购买限制',
            'grant_way'         => '获取方式',
            'name_series'       => '系列名称',
            'startline'         => '开始使用时间',
            'endline'           => '结束使用时间',
            'duction_rate'      => '折扣率',
            'duction_limit_min' => '折扣起售数量',
            'duction_limit_max' => '折扣限购数量',
            'mark'              => '备注',
            'saling_on_shop'    => '商店出售',
            'can_give'          => '物品是否能够赠送',
            'can_opened_by_box' => '是否礼盒开出',
            'is_continue'       => '是否续费才能用',
            'jump_page'         => '跳转页面',
            'ext_id'            => '物品视频文件关联礼物id',
            'ext_name'          => '物品视频文件关联礼物名称',
            'coupon_type'       => '优惠券类型',
            'scenario_type'     => '砸蛋券适用场景',
            'related_cid'       => '优惠券关联id',
            'duction_money'     => '优惠券金额',
            'only_newpay'       => '是否新支付',
            'period'            => '有效天数',
            'reward_cid'        => '奖励物品ID',
            'money'             => '收集金额总额',
            'period_hour'       => '有效时长',
            'discount'          => '折扣比例 min:0,max:30',
            'pretend_id'        => '装扮ID',
            'game_type'         => '门票类型',

        ];
    }

    /**
     * 提示信息
     */
    protected function messages(): array
    {
        return [
            'speed.regex' => '请填写正确的加速倍数',
        ];
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