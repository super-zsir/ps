<?php


namespace Imee\Models\Xss;


class CsmsStaff extends BaseModel
{


	const STATUS_DEFAULT = 0;
	const STATE_WORK = 1;
	const STATE_REST = 2;
	const STATE_LEAVE = 3;

	public static $state = [
		self::STATUS_DEFAULT => '默认',
		self::STATUS_NORMAL => '工作中',
		self::STATE_REST => '休息中',
		self::STATE_LEAVE => '离开中'
	];


	const MANAGER_NO = 0;
	const MANAGER_YES = 1;
	public static $manager = [
        self::MANAGER_YES => '全局',
		self::MANAGER_NO => '任务审核'
	];

}