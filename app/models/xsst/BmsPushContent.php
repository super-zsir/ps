<?php

namespace Imee\Models\Xsst;

class BmsPushContent extends BaseModel
{
	protected static $_createTimeField = 'create_time';

	protected static $_updateTimeField = 'update_time';

	const PUSH_MODE_CONDITION = 1;
	const PUSH_MODE_LIST = 2;
	/**
	 * 类型
	 */
	public static $pushMode = [
		self::PUSH_MODE_CONDITION => '条件推送',
		self::PUSH_MODE_LIST      => '名单推送',
	];

	public static $pushChannel = [
		'1' => '系统通知',
	];

	public static $pushType = [
		'1' => '纯文本',
		'2' => '纯图片',
		'3' => '文本链接',
		'4' => '图文链接',
	];

	const PUSH_STATE_0 = 0;
	const PUSH_STATE_1 = 1;
	const PUSH_STATE_2 = 2;
	const PUSH_STATE_3 = 3;
	const PUSH_STATE_4 = 4;
	const PUSH_STATE_5 = 5;
	/**
	 * 状态
	 */
	public static $pushState = [
		'0' => '待发送',
		'1' => '发送中',
		'2' => '已发送',
		'3' => '待搜索',
		'4' => '搜索中',
		'5' => '没有记录'];

	const SYSTEM_SENDER = 1;
	const KEFU_SENDER = 2;
	/**
	 * 通道
	 */
	public static $sender = [
		self::SYSTEM_SENDER => '系统通知',
		self::KEFU_SENDER   => '客服团队'
	];
}