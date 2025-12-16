<?php

namespace Imee\Service\Operate\Vip;

use Imee\Exception\ApiException;
use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xsst\XsstVipSendLimit;
use Imee\Service\Helper;
use Imee\Service\Operate\VipsendService;
use Imee\Service\StatusService;

class VipSendLimitService
{
    public function getListAndTotal(array $params): array
    {
        $params = Helper::trimParams($params);
        $condition = [];

        if (!empty($params['bigarea_id'])) {
            $condition[] = ['bigarea_id', '=', $params['bigarea_id']];
        }
        if (!empty($params['vip'])) {
            $condition[] = ['vip', '=', $params['vip']];
        }
        if (!empty($params['operator'])) {
            $condition[] = ['operator', 'like', $params['operator']];
        }
        $data = XsstVipSendLimit::getListAndTotal($condition, '*', 'id desc', $params['page'] ?? 1, $params['limit'] ?? 15);
        if (empty($data['data'])) {
            return $data;
        }

        foreach ($data['data'] as &$item) {
            $item['create_time'] = Helper::now($item['create_time']);
            $item['update_time'] = $item['update_time'] > 0 ? Helper::now($item['update_time']) : '';
        }

        return $data;
    }

    public function create(array $params): array
    {
        $this->validate($params);
        $data = [
            'bigarea_id' => $params['bigarea_id'],
            'vip'        => $params['vip'],
            'period'     => $params['period'],
            'num'        => $params['num'],
            'operator'   => Helper::getSystemUserInfo()['user_name'],
        ];

        [$res, $id] = XsstVipSendLimit::add($data);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $id);
        }

        return ['id' => $id, 'after_json' => $data];
    }

    public function modify(array $params): array
    {
        $this->validate($params);

        $id = $params['id'];
        $info = XsstVipSendLimit::findOne($id);

        $data = [
            'bigarea_id' => $params['bigarea_id'],
            'vip'        => $params['vip'],
            'period'     => $params['period'],
            'num'        => $params['num'],
            'operator'   => Helper::getSystemUserInfo()['user_name'],
        ];

        [$res, $msg] = XsstVipSendLimit::edit($id, $data);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        return ['id' => $id, 'before_json' => $info, 'after_json' => $data];
    }

    public function delete(array $params): array
    {
        $id = $params['id'];
        $info = XsstVipSendLimit::findOne($id);
        if (!$info) {
            throw new ApiException(ApiException::MSG_ERROR, '参数有误');
        }

        XsstVipSendLimit::deleteById($id);

        return ['id' => $id, 'before_json' => $info, 'after_json' => []];
    }

    private function validate(array $params): void
    {
        if (in_array($params['vip'], [7, 8]) && !VipsendService::hasVip7Purview()) {
            throw new ApiException(ApiException::MSG_ERROR, sprintf('VIP%s等级不可配置', $params['vip']));
        }

        $condition = [];
        $condition[] = ['bigarea_id', '=', $params['bigarea_id']];
        $condition[] = ['vip', '=', $params['vip']];
        if (!empty($params['id'])) {
            $condition[] = ['id', '<>', $params['id']];
        }
        $rec = XsstVipSendLimit::findOneByWhere($condition, 'id');
        if ($rec) {
            throw new ApiException(ApiException::MSG_ERROR, '大区、VIP等级不能重复');
        }
    }

    public function getBigareaMap($value = null)
    {
        $map = XsBigarea::getAllNewBigArea();

        if (is_numeric($value)) {
            return $map[$value] ?? '';
        }

        return StatusService::formatMap($map);
    }

    public function getVipMap($value = null)
    {
        $map = [
            1 => 'vip1',
            2 => 'vip2',
            3 => 'vip3',
            4 => 'vip4',
            5 => 'vip5',
            6 => 'vip6',
            7 => 'vip7',
            8 => 'vip8',
        ];

        if (!VipsendService::hasVip7Purview()) {
            unset($map[7]);
            unset($map[8]);
        }

        if (is_numeric($value)) {
            return $map[$value] ?? '';
        }

        return StatusService::formatMap($map);
    }

    public function getPeriodMap($value = null)
    {
        $map = [
            XsstVipSendLimit::PERIOD_MONTH      => '月度',
            XsstVipSendLimit::PERIOD_MONTH_HALF => '半月',
        ];

        if (is_numeric($value)) {
            return $map[$value] ?? '';
        }

        return StatusService::formatMap($map);
    }
}