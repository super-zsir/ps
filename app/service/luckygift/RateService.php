<?php

namespace Imee\Service\Luckygift;

use Imee\Models\Xs\XsLuckyGiftRate;
use Imee\Models\Xs\XsLuckyGiftRateAdjustment;
use Imee\Models\Xsst\BmsOperateHistory;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;

class RateService
{
    /**
     * @var PsService $rpcService ;
     */
    private $rpcService;

    public function __construct()
    {
        $this->rpcService = new PsService();
    }

    public function getRateList(array $params): array
    {
        $conditions = [
            ['is_delete', '=', 0],
            ['proportion', '=', $params['proportion']],
            ['property', '=', $params['property']]
        ];
        $res = XsLuckyGiftRate::getListAndTotal($conditions, '*', 'id desc', $params['page'], $params['limit']);
        $totalWeight = XsLuckyGiftRate::getWeightSum($conditions);
        $ids = array_column($res['data'], 'id');
        $logs = BmsOperateHistory::getLatestUpdateLog('xs_luck_gift_rate', $ids);
        if (empty($res['data'])) {
            return ['data' => [], 'total' => 0];
        }
        foreach ($res['data'] as &$v) {
            $v['probability'] = number_format($v['weight'] / $totalWeight * 100, 2) . '%';
            $v['admin_name'] = $logs[$v['id']]['update_uname'] ?? '-';
            $v['dateline'] = $logs[$v['id']]['dateline'] ?? '-';
        }
        return $res;
    }

    public function rateAdd(array $params): array
    {
        $info = XsLuckyGiftRate::findOneByWhere([
            ['rate', '=', $params['rate']],
            ['property', '=', $params['property']],
            ['proportion', '=', $params['proportion']],
            ['is_delete', '=', 0]
        ]);
        if (!empty($info)) {
            return [false, '当前倍数项已设置'];
        }
        $data = [
            'rate' => $params['rate'],
            'weight' => $params['weight'],
            'property' => $params['property'],
            'proportion' => $params['proportion']
        ];
        [$res, $msg, $id] = $this->rpcService->luckyGiftRateAdd($data);
        if ($res) {
            BmsOperateHistory::insertLog('xs_luck_gift_rate', $id, $data, Helper::getSystemUid());
            return [true, ''];
        }
        return [false, $msg];
    }

    public function rateEdit(array $params): array
    {
        $info = XsLuckyGiftRate::findOne($params['id']);
        if (empty($info)) {
            return [false, '当前配置不存在'];
        }
        $data = [
            'id' => $params['id'],
            'rate' => $info['rate'],
            'weight' => $params['weight'],
            'property' => $params['property']
        ];
        [$res, $msg] = $this->rpcService->luckyGiftRateEdit($data);
        if ($res) {
            BmsOperateHistory::insertLog('xs_luck_gift_rate', $params['id'], $data, Helper::getSystemUid());
            return [true, ''];
        }
        return [false, $msg];
    }

    public function rateDelete(int $id): array
    {
        $info = XsLuckyGiftRate::findOne($id);
        if (empty($info)) {
            return [false, '当前配置不存在'];
        }
        if ($info['rate'] == 0) {
            return [false, '0倍数项不可删除'];
        }
        $data = [
            'is_delete' => 1
        ];
        [$res, $msg] = $this->rpcService->luckyGiftRateDelete($id);
        if ($res) {
            BmsOperateHistory::insertLog('xs_luck_gift_rate', $id, $data, Helper::getSystemUid());
            return [true, ''];
        }
        return [false, $msg];
    }

    public function rateInfo(int $id): array
    {
        $info = XsLuckyGiftRate::findOne($id);
        if ($info) {
            $info = array_map('strval', $info);
        }
        return $info;
    }

    public function getDynamicRateList(array $params)
    {
        $conditions = [
            ['is_delete', '=', 0],
            ['property', '=', $params['property']],
            ['proportion', '=', $params['proportion']]
        ];
        $res = XsLuckyGiftRateAdjustment::getListAndTotal($conditions, '*', 'id desc', $params['page'], $params['limit']);
        $ids = array_column($res['data'], 'id');
        $logs = BmsOperateHistory::getLatestUpdateLog('xs_luck_gift_rate_adjustment', $ids);
        if (empty($res['data'])) {
            return ['data' => [], 'total' => 0];
        }
        foreach ($res['data'] as &$v) {
            $v['section'] = $v['start'] . '-' . $v['end'];
            $v['change'] = XsLuckyGiftRateAdjustment::$changeMap[$v['change']];
            $v['admin_name'] = $logs[$v['id']]['update_uname'] ?? '-';
            $v['dateline'] = $logs[$v['id']]['dateline'] ?? '-';
        }
        return $res;
    }

    public function dynamicRateAdd(array $params): array
    {
        $data = [
            'start' => $params['start'],
            'end' => $params['end'],
            'change' => $params['change'],
            'property' => $params['property'],
            'rate' => $params['rate'],
            'expectation' => $params['expectation'],
            'proportion' => $params['proportion']
        ];
        [$res, $msg, $id] = $this->rpcService->luckyGiftRateAdjustAdd($data);
        if ($res) {
            BmsOperateHistory::insertLog('xs_luck_gift_rate_adjustment', $id, $data, $params['admin_uid']);
            return [true, ''];
        }
        return [false, $msg];
    }

    public function dynamicRateEdit(array $params): array
    {
        if (isset($params['range_start'])) {
            $params['start'] = $params['range_start'];
        }
        $data = [
            'id' => $params['id'],
            'start' => $params['start'],
            'end' => $params['end'],
            'change' => $params['change'],
            'property' => $params['property'],
            'rate' => $params['rate'],
            'expectation' => $params['expectation'],
        ];
        [$res, $msg] = $this->rpcService->luckyGiftRateAdjustEdit($data);
        if ($res) {
            BmsOperateHistory::insertLog('xs_luck_gift_rate_adjustment', $params['id'], $data, $params['admin_uid']);
            return [true, ''];
        }
        return [false, $msg];
    }

    public function dynamicRateDelete(int $id): array
    {
        $data = [
            'is_delete' => 1
        ];
        [$res, $msg] = $this->rpcService->luckyGiftRateAdjustDelete($id);
        if ($res) {
            BmsOperateHistory::insertLog('xs_luck_gift_rate_adjustment', $id, $data, Helper::getSystemUid());
            return [true, ''];
        }
        return [false, $msg];
    }

    public function dynamicRateInfo(int $id): array
    {
        $info = XsLuckyGiftRateAdjustment::findOne($id);
        if ($info) {
            $info = array_map('strval', $info);
        }
        return $info;
    }
}