<?php


namespace Imee\Models\Xss;


class XssFirstChatRecord extends BaseModel
{
	const REPLY_NO = 0;
	const REPLY_YES = 1;

	public static $isReply = [
		self::REPLY_NO => '否',
		self::REPLY_YES => '是',
	];
}