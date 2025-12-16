<?php
namespace Imee\Models\Xs;

class XsUserExposure extends BaseModel
{
	public static function getValueByUid($uid)
	{
		return self::findFirst(array(
			"uid=:uid:",
			"bind" => array("uid" => $uid)
		));
	}
}
