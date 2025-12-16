<?php

namespace Imee\Service\Domain\Service\Csms\Process\Databoard;

use Imee\Exception\Audit\DataboardDbException;
use Imee\Models\Bms\BmsVerifyKanbanKpi;
use Imee\Models\Bms\BmsVerifyKanbanKpiLog;
use Imee\Service\Domain\Service\Csms\Context\Databoard\KpiLogContext;
use Imee\Service\Domain\Service\Traits\UserInfoTrait;

/**
 * kpi管理
 */
class KpiLogProcess
{
    use UserInfoTrait;
    /**
     * 保存日志
     * @param array $data
     * @return bool
     * @throws \ReflectionException
     */
    public function addKpiLog(array $data)
    {
        try {
            $condition = array(
                'exam_item' => $data['exam_item'],
                'arm' => $data['arm'],
                'operator_id' => $data['operator_id'] ?? 0,
                'operation' => $data['operation'] ?? '',
            );
            return BmsVerifyKanbanKpiLog::handleSave($condition);
        } catch (\Exception $e) {
            DataboardDbException::throwException(DataboardDbException::KPI_DB_ERROR, ['exception'=>$e->getMessage(),'trace' => $e->getTraceAsString()]);
        }
    }

    /**
     * 日志列表
     * @param KpiLogContext $context
     * @return array
     * @throws \ReflectionException
     */
    public function getKpiLog(KpiLogContext $context)
    {
        try {
            $condition = array(
                'columns' => 'exam_item, arm, operator_id, operation, create_time',
                'limit' => $context->limit,
                'offset' => $context->offset,
                'orderBy' => 'create_time desc',
                'exam_item' => $context->examItem,
            );
            $list = BmsVerifyKanbanKpiLog::handleList($condition);
            if ($list) {
                $operateIds = array_column($list, 'operator_id');
                $users = $this->getStaffBaseInfos($operateIds);
                foreach ($list as &$item) {
                    $item['operator'] = isset($users[$item['operator_id']]) ? $users[$item['operator_id']]['user_name'] : '';
                }
            }
            return $list;
        } catch (\Exception $e) {
            DataboardDbException::throwException(DataboardDbException::KPI_DB_GET_ERROR, ['exception'=>$e->getMessage(),'trace' => $e->getTraceAsString()]);
        }
    }

    /**
     * 日志总数
     * @param KpiLogContext $context
     * @return int
     * @throws \ReflectionException
     */
    public function getKpiLogTotal(KpiLogContext $context)
    {
        try {
            $condition = array(
                'columns' => 'operator_id, operation, create_time',
                'exam_item' => $context->examItem,
            );
            return BmsVerifyKanbanKpiLog::handleTotal($condition);
        } catch (\Exception $e) {
            DataboardDbException::throwException(DataboardDbException::KPI_DB_GET_ERROR, ['exception'=>$e->getMessage(),'trace' => $e->getTraceAsString()]);
        }
    }
}