<?php

namespace Imee\Models\Config;

class BbcCustomerServiceQuickReplyGroup extends BaseModel
{
	const DELETED_YES = 1;
	const DELETED_NO = 0;


	public static function getList($deleted = -1)
	{
		$model = self::query();
		if ($deleted >= 0) $model->andWhere('deleted = :deleted:', ['deleted' => intval($deleted)]);
		return $model->execute()->toArray();
	}

	public static function addGroup($group_name, $op_uid)
	{
		$group_name = trim($group_name);
		if ($group_name == '' || $op_uid <= 0) return false;

		$model = new self();
		$model->group_name = $group_name;
		$model->dateline = time();
		$model->update_time = time();
		$model->op_uid = $op_uid;
		return $model->save();
	}
}