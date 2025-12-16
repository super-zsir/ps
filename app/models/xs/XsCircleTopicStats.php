<?php

namespace Imee\Models\Xs;

class XsCircleTopicStats extends BaseModel
{
	protected $allowEmptyStringArr = ['pool', 'tagtype'];

	public static function getFilter($params)
	{
		$tpid   = $params['tpid'] ?? '';
		$tpuid  = $params['tpuid'] ?? '';
		$start  = $params['start'] ?? '';
		$end    = $params['end'] ?? '';
		$filter = ['app_id' => APP_ID];
		if ($tpid !== '' && $tpid >= 0) $filter['tpid'] = $tpid;
		if ($tpuid !== '' && $tpuid >= 0) $filter['tpuid'] = $tpuid;
		if (!empty($start)) $filter['dateline'][] = ['>=', strtotime($start)];
		if (!empty($end)) $filter['dateline'][] = ['<', strtotime($end)];
		return $filter;
	}
}
