<?php


namespace Imee\Models\Config;


class BbcBannerSettings extends BaseModel
{
	/**
	 * 同一个大区的同一个banner位置，最多允许同时启用6个banner
	 */
	const IN_USE_COUNT = 6;

	public static $display = [
		'home' => '首页',
		'post' => '动态页',
		'top_up' => '充值页',
	];

	public static $type = [
		'room' => '房间',
		'activity' => '落地页',
	];

	public static $in_use = [
		'0' => '不启用',
		'1' => '启用',
	];

	protected $allowEmptyStringArr = [
		'redirect','image_url'
	];
}