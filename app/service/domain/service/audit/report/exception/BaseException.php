<?php

namespace Imee\Service\Domain\Service\Audit\Report\Exception;

use Imee\Exception\ReportException;

class BaseException extends ReportException
{
    protected $moduleCode = '00';
    protected $serviceCode = '00';

    const PARAMS_ERROR = ['01', '参数有误'];
    const REJECT_NEED_REASON = ['02', '驳回需要填原因'];
    const DATA_ERROR = ['03', '数据出现问题'];
    const STATE_UNCHANGE = ['04', '状态未改变'];
    const DATE_ERROR_MSG_SEND_FAIL = ['05', '数据错误，信息发送失败'];
}