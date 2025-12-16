<?php


namespace Imee\Service\Domain\Service\Csms\Exception;

use Imee\Service\Domain\Service\Csms\Exception\Saas\BaseException;

class DataboardDbException extends BaseException
{
    protected $serviceCode = '13';

    const KPI_DB_ERROR = ['10', '保存kpi管理数据失败'];
    const KPI_DB_EDIT_ERROR = ['11', '修改kpi管理数据失败'];
    const KPI_DB_GET_ERROR = ['12', '查看kpi管理数据日志失败'];
    const KPI_ITEM_EXIST = ['13', '当前指标已存在'];
    const DAY_DETAIL_AUDIT_ERROR = ['14', '加载列表失败'];
    const STAFF_EXAM_INFO_ERROR = ['15', '加载列表失败'];
    const TIME_BOARD_LIST_ERROR = ['16', '加载分时列表失败'];

    const BEGIN_TIME_ERROR = ['17', '起始时间必须为周一'];
    const END_TIME_ERROR = ['18', '结束时间必须为周日'];
}
