<?php

namespace Imee\Service\Luckygift;

use Imee\Models\Xs\XsGift;
use Imee\Models\Xs\XsLuckyGiftDivided;
use Imee\Models\Xsst\BmsOperateHistory;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;

class AnchorDivideService
{
	public function getList(array $params, int $page, int $pageSize) : array
	{
		$result = XsLuckyGiftDivided::getListAndTotal([
			['is_delete', '=', 0]
		],'*', 'id desc', $page, $pageSize);
		if (empty($result['data'])) {
			return $result;
		}
		$ids = array_column($result['data'], 'id');
		$logs = BmsOperateHistory::getLatestUpdateLog('xs_luck_gift_divided', $ids);
		$giftIds = array_column($result['data'], 'gift_id');
		$giftIds = array_values(array_unique($giftIds));
		$giftList = XsGift::getBatchCommon($giftIds,['id', 'name'],'id');
		foreach ($result['data'] as &$v) {
			$v['proportion'] = (string) $v['proportion'];
			$v['gift_name'] = $giftList[$v['gift_id']]['name'] ?? '';
			$v['gift_id_name'] = (string) $v['gift_id'];
			$v['admin_name'] = $logs[$v['id']]['update_uname'] ?? '-';
			$v['dateline'] = $logs[$v['id']]['dateline'] ?? '-';
		}
		return $result;
	}

	public function add(int $proportion, int $giftId) : array
	{
		$insert = [
			'proportion' => $proportion,
			'gift_id' => $giftId
		];
		$insert = array_map('intval', $insert);
		$info = XsLuckyGiftDivided::findOneByWhere([
			['is_delete', '=', 0],
			['gift_id', '=', $giftId]
		]);
		if ($info) {
			return [false, '当前礼物已存在比例'];
		}
		[$res, $msg, $id] = (new PsService())->luckyGiftDividedAdd($insert);
		if ($res) {
			BmsOperateHistory::insertLog('xs_luck_gift_divided', $id, $insert, Helper::getSystemUid());
			return [true, ''];
		}
		return [false, $msg];
	}

	public function edit(int $id, int $proportion, int $giftId) : array
	{
		$data = [
			'id' => $id,
			'proportion' => $proportion,
			'gift_id' => $giftId
		];
		$data = array_map('intval', $data);
		[$res, $msg] = (new PsService())->luckyGiftDividedEdit($data);
		if ($res) {
			BmsOperateHistory::insertLog('xs_luck_gift_divided', $id, $data, Helper::getSystemUid());
			return [true, ''];
		}
		return [false, $msg];
	}

	public function delete(int $id) : array
	{
		[$res, $msg] = (new PsService())->luckyGiftDividedDelete($id);
		if ($res) {
			BmsOperateHistory::insertLog('xs_luck_gift_divided', $id, [], Helper::getSystemUid());
			return [true, ''];
		}
		return [false, $msg];
	}
}