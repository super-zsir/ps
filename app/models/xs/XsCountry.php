<?php

namespace Imee\Models\Xs;

class XsCountry extends BaseModel
{
	public static function getCountryFilter()
	{
		$data = [];
		$country = XsCountry::find();
		foreach ($country as $v) {
			$data[] = array($v["name_international_abbr"], $v['name_zh']."({$v["name_international_abbr"]})");
		}
		return $data;
	}

    /**
     * 获取列表枚举
     * @return array
     */
    public static function getListMap(): array
    {
        $list = self::getListByWhere([], 'id, name_zh');

        return $list ? array_column($list, 'name_zh', 'id') : [];
    }
}