<?php

namespace Imee\Models\Xsst;

class BmsAppVersion extends BaseModel
{
	protected static $_createTimeField = 'dateline';

	const PLATFORM_IOS = 2;
	const PLATFORM_ANDROID = 1;

	public static $platform = [
		self::PLATFORM_ANDROID => 'Android-',
		self::PLATFORM_IOS     => 'IOS-'
	];

	public static function getAppVersions(int $app_id): array
	{
        return self::find([
            'conditions' => 'app_id=:app_id:',
            'bind'       => compact('app_id')
        ])->toArray();
	}

	public static function getFilter($params)
	{
		$platform = $params['platform'] ?? '';
		$version  = $params['version'] ?? '';

		$filter           = [];
		$filter['app_id'] = APP_ID;
		if ($platform !== '' && $platform >= 0) $filter['platform'] = $platform;
		if ($version != '') $filter['version'] = ['LIKE', $version];

		return $filter;
	}
}