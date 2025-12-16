<?php

namespace Imee\Models\Xs;

class XsUserReaudit extends BaseModel
{
	protected $allowEmptyStringArr = ['name', 'sign'];

	const STATUS_UNCHECK = 1;
	const STATUS_CHECK_ERROR = 2;
	const STATUS_CHECK_CORRECT = 3;
	const STATUS_HANDLED = 4;

	public static $status_arr = [
		self::STATUS_UNCHECK => '待处理',
		self::STATUS_CHECK_ERROR => '错误识别',
		self::STATUS_CHECK_CORRECT => '正确识别',
//		self::STATUS_HANDLED => '已被处理',
	];
}