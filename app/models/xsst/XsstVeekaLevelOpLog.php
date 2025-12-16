<?php

namespace Imee\Models\Xsst;

class XsstVeekaLevelOpLog extends BaseModel
{
	const TYPE_ACTIVITY = 1;
	const TYPE_CHARM = 2;
	const TYPE_VIP = 3;
	const TYPE_TITLE = 4;

	const TYPE_MAP = [
		self::TYPE_ACTIVITY => '活跃度',
		self::TYPE_CHARM => '人气值',
		self::TYPE_VIP => 'VIP值',
		self::TYPE_TITLE => '爵位值',
	];
}
