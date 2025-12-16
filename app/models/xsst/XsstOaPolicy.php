<?php

namespace Imee\Models\Xsst;

use Imee\Service\Helper;

class XsstOaPolicy extends BaseModel
{
	const FIELDS = ['oa_num'];

	public static function saveOne($data)
	{
		$res = self::findFirst([
//			'oa_num = :oa_num: and policy_num = :policy_num: and policy_name = :policy_name: and policy_version = :policy_version: and settlement_cycle=:settlement_cycle:',
			'oa_num = :oa_num:',
			'bind' => $data,
		]);
		if ($res) return $res->id;

		$res = new static();
        $data['dateline'] = time();
		$res->assign($data);
		$res->save();
		return $res->id ?? 0;
	}

	public static function getRelationOa($relIds, $scene): array
	{
		if ($scene != XsstOaRelation::OA_TYPE_ADD_MONEY) return []; // 目前只支持加钱
		if (empty($relIds)) return [];
		$relIds = implode(',', $relIds);
		$scene = intval($scene);
		$oa = Helper::fetch("select r.relation_id,a.oa_num from xsst_oa_policy as a join xsst_oa_relation as r on r.oa_id = a.id where r.relation_id in ({$relIds}) and r.scene = {$scene}", [], BaseModel::SCHEMA_READ);
		return array_column($oa, null, 'relation_id');
	}
}