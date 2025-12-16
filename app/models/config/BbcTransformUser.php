<?php

namespace Imee\Models\Config;

class BbcTransformUser extends BaseModel
{
	public static function updateData($data)
	{
		try {
			$rec = self::useMaster()->findFirst(array(
				"user_id=:user_id:",
				"bind" => array("user_id" => $data['uid'])
			));

			if(!$rec) return false;

			$rec->admin_language = $data['language'];
			return $rec->save();
		} catch (\Exception $e) {
			return false;
		}
		return false;
	}


}