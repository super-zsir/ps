<?php

namespace Imee\Models\Xs;

class XsLuckyGiftRateAdjustment extends BaseModel
{
	protected static $primaryKey = 'id';

	public static $changeMap = [
		1 => '增加',
		2 => '减少'
	];
}