<?php

namespace Imee\Service\Commodity;

use Imee\Comp\Common\Log\Service\OperateLog;
use Imee\Helper\Traits\SingletonTrait;
use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xsst\BmsOperateLog;
use Imee\Models\Xsst\XsstCommodityBudget;
use Imee\Models\Xsst\XsstCommoditySend;

class CommodityBudgetService
{
    use SingletonTrait;

    public function getTypes(): array
    {
        return XsstCommodityBudget::$commodityBudgetTypes;
    }

    public function getList(array $params): array
    {
        $id = intval($params['id'] ?? 0);
        $bigareaId = intval($params['bigarea_id'] ?? 0);
        $type = trim($params['type'] ?? '');
        $period = trim($params['period'] ?? '');
        $periodStart = trim($params['period_sdate'] ?? '');
        $periodEnd = trim($params['period_edate'] ?? '');

        $conditions = [];
        if ($id > 0) {
            $conditions[] = ['id', '=', $id];
        }
        if ($bigareaId > 0) {
            $conditions[] = ['bigarea_id', '=', $bigareaId];
        }
        if (!empty($type)) {
            $conditions[] = ['type', '=', $type];
        }
        if (!empty($period)) {
            $period = strtotime(date('Y-m-01', strtotime($period)));
            if ($period > 0) {
                $conditions[] = ['period', '=', $period];
            }
        }
        if (!empty($periodStart) && !empty($periodEnd)) {
            $periodStart = strtotime(date('Y-m-01', strtotime($periodStart)));
            $periodEnd = strtotime(date('Y-m-01', strtotime($periodEnd)));
            $conditions[] = ['period', '>=', $periodStart];
            $conditions[] = ['period', '<=', $periodEnd];
        }
        $data = XsstCommodityBudget::getListAndTotal($conditions, '*', 'id DESC', $params['page'] ?? 1, $params['limit'] ?? 15);
        if (empty($data['data'])) {
            return $data;
        }

        foreach ($data['data'] as &$rec) {
            $rec['consume'] = XsstCommoditySend::getPrice($rec['id'], $rec['period']);
            $rec['balance'] = ($rec['price'] - $rec['consume']) / 100;
            $rec['consume'] = $rec['consume'] / 100;
            $rec['bigarea_id'] = (string)$rec['bigarea_id'];
            $rec['period'] = date('Y-m', $rec['period']);
            $rec['price'] = $rec['price'] / 100;
        }
        return $data;
    }


    public function create(array $params): array
    {
        $bigareaId = intval($params['bigarea_id'] ?? 0);
        $type = trim($params['type'] ?? '');
        $period = trim($params['period'] ?? '');
        $price = trim($params['price'] ?? 0);

        if ($bigareaId < 1 || empty($type) || empty($period) || !is_numeric($price) || $price < 1) {
            return [false, '提交数据有误'];
        }
        if (!array_key_exists($type, $this->getTypes())) {
            return [false, '物品类型不支持'];
        }
        $period = strtotime(date('Y-m-01', strtotime($period)));

        $rec = XsstCommodityBudget::findOneByWhere([
            ['bigarea_id', '=', $bigareaId],
            ['type', '=', $type],
            ['period', '=', $period],
        ]);
        if (!empty($rec)) {
            return [false, '该预算已存在'];
        }

        $params = [
            'bigarea_id' => $bigareaId,
            'type' => $type,
            'period' => $period,
            'price' => $price * 100
        ];
        [$success, $id] =  XsstCommodityBudget::add($params);

        if ($success) {
            $logData = [
                'content'      => $bigareaId.'-'.$type.'-'.date('Ym', $period),
                'after_json'   => $params,
                'type'         => BmsOperateLog::TYPE_OPERATE_LOG,
                'model'        => XsstCommodityBudget::getTableName(),
                'model_id'     => $id,
                'action'       => BmsOperateLog::ACTION_ADD,
            ];
            OperateLog::addOperateLog($logData);
        }

        return [$success, $id];
    }

    public function modify(array $params): array
    {
        $id = intval($params['id'] ?? 0);
        $reason = trim($params['reason'] ?? '');
        if (empty($reason)) {
            return [false, '请填写原因'];
        }
        $changeType = $params['change_type'] ?? 1;
        $change = $params['change'] ?? 0;
        $rec = XsstCommodityBudget::findOne($id);
        if (empty($rec)) {
            return [false, '数据提交有误'];
        }
        $price = XsstCommodityBudget::TYPE_DELETE == $changeType ? ($rec['price']/100 - $change) : ($rec['price']/100 + $change);
        if ($price < 0) {
            return [false, '数据提交有误'];
        }

        $consume = XsstCommoditySend::getPrice($id, $rec['period']);

        if ($price * 100 < $consume) {
            return [false, '预算金额不能小于已经发放的物品总价'];
        }

        [$success, $msg] = XsstCommodityBudget::edit($id, [
            'price' => $price * 100,
            'reason' => $reason
        ]);

        if ($success) {
            $logData = [
                'content'      => $rec['bigarea_id'].'-'.$rec['type'].'-'.date('Ym', $rec['period']),
                'before_json'  => ['price' => $rec['price'], 'reason' => $rec['reason']],
                'after_json'   => ['price' => $price * 100, 'reason' => $reason],
                'type'         => BmsOperateLog::TYPE_OPERATE_LOG,
                'model'        => XsstCommodityBudget::getTableName(),
                'model_id'     => $id,
                'action'       => BmsOperateLog::ACTION_UPDATE,
            ];
            OperateLog::addOperateLog($logData);
        }

        return [$success, $msg];
    }

    public function delete(array $params): array
    {
        $id = intval($params['id'] ?? 0);

        if ($id < 1) {
            return [false, '非法操作'];
        }

        $rec = XsstCommoditySend::findOneByWhere([['budget_id', '=', $id], ['state', 'in', [0, 1]]]);
        if (!empty($rec)) {
            return [false, '该预算已使用，不支持删除'];
        }

        $rec = XsstCommodityBudget::findOne($id);

        if (XsstCommodityBudget::deleteById($id)) {
            $logData = [
                'content'      => $rec['bigarea_id'].'-'.$rec['type'].'-'.date('Ym', $rec['period']),
                'after_json'   => ['id' => $id],
                'type'         => BmsOperateLog::TYPE_OPERATE_LOG,
                'model'        => XsstCommodityBudget::getTableName(),
                'model_id'     => $id,
                'action'       => BmsOperateLog::ACTION_DEL,
            ];
            OperateLog::addOperateLog($logData);

            return [true, ''];
        }
        return [false, '操作失败'];
    }

    public function multiCreate(array $params): array
    {
        $data = $params['data'] ?? [];
        if (empty($data)) {
            return [false, '数据提交错误'];
        }
        foreach ($data as $rec) {
            $this->create($rec);
        }
        return [true, ''];
    }

    public function getInfo(int $id): array
    {
        $data = XsstCommodityBudget::findOne($id);
        if (empty($data)) {
            return [];
        }

        $data['bigarea_name'] = XsBigarea::getBigAreaCnNameById($data['bigarea_id']);
        $data['type'] = ($this->getTypes())[$data['type']] ?? '';
        $data['period'] = date('Y-m', $data['period']);
        $data['price'] = $data['price'] / 100;

        return $data;
    }
}