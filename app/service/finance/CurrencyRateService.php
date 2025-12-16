<?php

namespace Imee\Service\Finance;

use Imee\Exception\ApiException;
use Imee\Models\Recharge\XsExchangeRate;

class CurrencyRateService
{
    public function getData($params)
    {
        $condition = self::parseWhere($params);
        $result = XsExchangeRate::getListAndTotal($condition, '*', 'id desc', $params['page'], $params['limit']);
        $result['data'] = self::handleList($result['data']);
        return $result;
    }

    private function parseWhere($params)
    {
        $condition = [];
        if (isset($params['cate']) && $params['cate']) {
            $condition[] = ['cate', '=', $params['cate']];
        }
        if (isset($params['dateline']) && $params['dateline']) {
            $condition[] = ['dateline', '=', strtotime($params['dateline'])];
        } else {
            $condition[] = ['dateline', '=', strtotime(date('Y-m-d'))];
        }
        return $condition;
    }

    private function handleList($data)
    {
        foreach ($data as &$v)
        {
            $v['cate'] = XsExchangeRate::CATE_MAP[$v['cate']] ?? $v['cate'];
            $v['rate'] = rtrim(rtrim($v['rate'], '0'), '.');
            $v['urate'] = rtrim(rtrim($v['urate'], '0'), '.');
            $v['dateline'] = date('Y-m-d', $v['dateline']);
        }
        return $data;
    }

    public function calculateRate($params)
    {
        if (is_numeric($params['amount']) == false || floatval($params['amount']) <= 0) {
            throw new ApiException(ApiException::MSG_ERROR, '计算金额必须为大于0的数字');
        } else {
            $amount = floatval($params['amount']);
        }
        if (!isset($params['id']) || !is_numeric($params['id'])) {
            $condition = [
                ['cate', '=', $params['cate']],
                ['dateline', '=', strtotime($params['dateline'])]
            ];
            $data = XsExchangeRate::findOneByWhere($condition);
        } else {
            $data = XsExchangeRate::findOne($params['id']);
        }
        if (!$data) {
            throw new ApiException(ApiException::MSG_ERROR, '汇率记录不存在');
        }
        $usd = bcadd('0', bcmul($amount, $data['urate'], 10), 2);
        return [true, ['is_confirm' => 1, 'confirm_text' => '按当前汇率计算，' . $amount . ' ' . $data['cate'] . ' 可兑换 ' . $usd . ' USD'] ];
    }
}
