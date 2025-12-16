<?php


namespace Imee\Models\Xss;


class XssFirstChatMessage extends BaseModel
{
	protected $allowEmptyStringArr = [
		'channel_type',
		'msg_uid',
		'target_id',
		'source'
	];
}