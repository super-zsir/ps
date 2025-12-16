<?php

namespace Imee\Models\Xs;

class XsDangerHistory extends BaseModel
{
	protected $allowEmptyStringArr = ['detail'];

	const RISK_REASON_MAP = [
		'danger.same.account' => '支付账号被撤单用户使用过',
		'ip.different' => '创建订单和回调IP不同',
		'ip.pay.account' => '充值IP一定时间里被多账户使用',
		'card.number' => '支付账户被多人使用',
		'ip.num' => '用户IP变化频繁',
		'phone.ip' => '创建订单地区与手机地区不一致',
		'card.country.phone' => '信用卡发卡行国家与注册手机地区不一致',
		'card.alpha.empty' => '信用卡发卡行国家不能识别',
		'account.request' => '信用卡发卡行地区与用户创建订单国家不一致',
		'account.number' => '支付账户过多',
		'none' => '无风控原因的',
		'hand.add.pay' => '手动添加风险用户',
		'danger.relate.account' => '撤单用户的关联账号',
	];
}
