<?php
namespace Imee\Exception\Cs;

class StatisticsException extends BaseException
{
    protected $serviceCode = '07';
    const TIME_RANGE_ERROR = ['00', '最大查询日期范围%s天'];
    const AUTO_CHAT_DETAILS_ERROR = ['01', '获取详情失败'];
    const DATA_NO_EXIST_ERROR = ['02', '数据不存在'];
}
