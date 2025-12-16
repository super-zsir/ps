<?php


namespace Imee\Models\Xss;


class CsmsAuditAudio extends BaseModel
{

	public static $status = [
		0 => '待审核',
		1 => '审核通过',
		2 => '审核不通过',
		4 => '待审核',
	];

}