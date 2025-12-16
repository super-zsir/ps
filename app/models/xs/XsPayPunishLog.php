<?php

namespace Imee\Models\Xs;

class XsPayPunishLog extends BaseModel
{
	const ACCOUNT_TYPE = [
		'0' => [
			'unit' => '钻石',
			'account' => '钻石余额',
		],
		'4' => [
			'unit' => '币商钻石',
			'account' => '代充转账钻石',
		],
		'5' => [
			'unit' => '魅力值',
			'account' => '魅力值',
		],
		'6' => [
			'unit' => '金豆',
			'account' => '金豆',
		],
		'7' => [
			'unit' => '现金',
			'account' => '现金',
		],
		'8' => [
			'unit' => '金币',
			'account' => '金币',
		],
	];

	const OP_TYPE = [
		1 => '转账充值-代充',
		2 => '转账充值-自充',
		3 => '薪资抵扣-代充',
		4 => '薪资抵扣-自充',
		5 => '运营奖励',
		6 => '测试',
		7 => '其他',
		8 => '薪资结算',
		9 => '补钻',
		10 => '活动奖励',
		11 => '薪资预支-钻石/币商钻石',
		12 => '薪资预支-现金',
	];

	// 需要填写充值金额的加钱类型
	const RECHARGE_NEED_OP_TYPES = [1, 2, 3, 4, 11]; // 充值金额：单位美元，加钱类型是转账充值、薪资抵扣和薪资预支-钻石/币商钻石时必填，其他类型时无需填写

	const SALARY_IN_ADVANCE = [11 => [0, 4], 12 => [7]]; // 当加钱类型为11薪资预支-钻石/币商钻石时，需要填写充值金额，账户类型只可以选择0或4, 当加钱类型为12薪资预支-现金时，账户类型只可以选择7现金

	const TYPE_MAP = [
		'1' => ['text' =>'罚款', 'color' => 'red',],
		'2'=> ['text' =>'加钱', 'color' => 'green',],
		'3'=> ['text' =>'冻结', 'color' => 'blue',],
		'4'=> ['text' =>'指定账户罚款', 'color' => 'orange',],
	];

	// 冻结类型
	const FROZEN_TYPE_MAP = [
		'cancelOrder' => ['text' =>'撤单', 'color' => 'red',],
		'chasingMoney'=> ['text' =>'追款', 'color' => 'green',],
	];

}
