<?php

namespace Imee\Models\Xs;

class XsUserTitleConfigNew extends BaseModel
{
    protected static $primaryKey = 'level';

	public static function getAllTitleName()
	{
		$res = self::getListByWhere([], 'level,name');
		return array_column($res, 'name', 'level');
	}

    public static function getNewTitleLabel()
    {
        $data = [];
        $list = self::getAllTitleName();
        foreach ($list as $level => $name) {
            $data[strval($level)] = [$level, $name];
        }
        return $data;
    }

    public static function getUserTitleNewList()
    {
        $titles = self::getListByWhere([], 'level, name', 'level asc');

        if (!empty($titles)) {
            $titles = array_column($titles, 'name', 'level');
        }

        return $titles;
    }
}